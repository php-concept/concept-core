<?php declare(strict_types=1);

namespace Concept\Core\Http\Requests;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Logger\Contracts\LoggerInterface;
use Concept\Core\Dto\Contracts\DtoInterface;
use Concept\Core\Components\Caster\Contracts\CasterInterface;
use Concept\Core\Components\Caster\Exceptions\CastingException;
use Concept\Core\Http\RequestFormat;
use Concept\Core\Http\SessionKey;
use Concept\Core\Components\Validator\Contracts\ValidationInterface;
use Concept\Core\Components\Validator\Contracts\ValidatorInterface;
use Concept\Core\Components\Validator\Exceptions\ValidationCastException;
use Concept\Core\Components\Validator\Exceptions\ValidationLogicException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @template T of DtoInterface
 */
abstract class FormRequest implements FormRequestInterface
{
    private const string LOG_INCOMING_DATA_FOR_VALIDATION = 'Validation incoming data [%s]';
    private const string LOG_VALIDATED_DATA = 'Validated data for [%s]';
    private const string LOG_PAYLOAD_IS_VALID = 'is_valid';
    private const string LOG_PAYLOAD_VALID_DATA = 'valid_data';
    private const string LOG_PAYLOAD_ERRORS = 'errors';
    private const string LOG_PAYLOAD_URI = 'uri';
    private const string LOG_PAYLOAD_DATA = 'data';

    /**
     * Fields that should be excluded from validation for all requests
     *
     * @var array<string>
     */
    private array $globalExcept = [
        SessionKey::CSRF_TOKEN,
    ];

    /**
     * Fields that should be excluded from validation
     *
     * @var array<string>
     */
    protected array $except = [];


    /**
     * Fields that should be validated only
     *
     * @var array<string>
     */
    protected array $only = [];

    /**
     * Class name of DTO that may be created from validated data
     *
     * @var class-string<T>|null
     */
    protected ?string $dtoClass = null;

    protected ValidationInterface $validation;

    public function __construct(
        protected readonly ServerRequestInterface $request,
        protected readonly RequestFormat $requestFormat,
        protected readonly ConfigInterface $config,
        protected readonly LoggerInterface $logger,
        protected readonly ValidatorInterface $validator,
        protected readonly ?CasterInterface $caster = null
    ) {}

    /**
     * @return array<string, string>|array<string, string[]>
     */
    abstract public function rules(): array;

    /**
     * @return array<string, string>
     */
    public function aliases(): array
    {
        return [];
    }

    public function validate(): bool
    {
        $this->validation = $this->validator->make($this->all(), $this->rules());

        if (!empty($this->aliases())) {
            $this->validation->setAliases($this->aliases());
        }

        $this->validation->validate();
        $isValid = $this->validation->isValid();

        if ($this->config->getBool('log.validation_data', false)) {
            $this->logger->debug(sprintf(self::LOG_VALIDATED_DATA, static::class), [
                self::LOG_PAYLOAD_IS_VALID => $isValid,
                self::LOG_PAYLOAD_VALID_DATA => $this->validation->getValidData(),
                self::LOG_PAYLOAD_ERRORS => $this->validation->getErrors(),
            ]);
        }

        return $isValid;
    }

    /**
     * @return array<mixed>
     */
    public function validated(): array
    {
        if (!isset($this->validation)) {
            throw new ValidationLogicException(self::class);
        }

        // 1. Get the validated data
        $data = $this->validation->getValidData();

        // 2. Get the allowed keys from the rules - this is our "white list"
        // We use array_keys to get the keys, to get fieldset names
        $allowedKeys = array_keys($this->rules());

        // 3. Filter the data to only include the allowed keys
        $validatedData = array_intersect_key($data, array_flip($allowedKeys));

        // 4. If $this->only is not empty, only include the specified keys
        if (!empty($this->only)) {
            $validatedData = array_intersect_key($validatedData, array_flip($this->only));
        }

        // 5. Exclude technical fields (CSRF) and fields from $this->except
        $exclude = array_merge($this->globalExcept, $this->except);
        $validatedData = array_diff_key($validatedData, array_flip($exclude));

        return $validatedData;
    }

    /**
     * @return array<mixed>
     */
    public function errors(): array
    {
        return $this->validation->getErrors();
    }

    /**
     * @return array<mixed>
     */
    public function all(): array
    {
        $body = $this->request->getParsedBody();
        $parsedBody = is_array($body) ? $body : (is_object($body) ? (array)$body : []);

        $data = array_merge($this->request->getQueryParams(), $parsedBody);
        if ($this->config->getBool('log.validation_data', false)) {
            $this->logger->debug(sprintf(self::LOG_INCOMING_DATA_FOR_VALIDATION, static::class), [
                self::LOG_PAYLOAD_URI => $this->request->getUri()->getPath(),
                self::LOG_PAYLOAD_DATA => $data,
            ]);
        }

        return $data;
    }

    /**
     * @return T|null
     * @throws ValidationCastException
     */
    public function toDto(): ?DtoInterface
    {
        if ($this->dtoClass === null || !($this->caster instanceof CasterInterface)) {
            return null;
        }

        if(class_exists($this->dtoClass)) {
            /** @var T $dto */
            $dto = $this->castValue($this->validated(), $this->dtoClass);
            return $dto;
        }

        return null;
    }

    protected function getRouteParam(string $key, mixed $default = null): mixed
    {
        return $this->request->getAttribute($key, $default);
    }

    private function castValue(mixed $value, ?string $type): mixed
    {
        if ($type === null || !($this->caster instanceof CasterInterface)) {
            return $value;
        }

        try {
            return $this->caster->cast($value, $type);
        } catch (CastingException $e) {
            throw new ValidationCastException($e->getMessage(), $e->getCode(), $e);
        }
    }
}