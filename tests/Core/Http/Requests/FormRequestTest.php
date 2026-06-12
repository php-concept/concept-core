<?php declare(strict_types=1);

namespace Tests\Core\Http\Requests;

use Concept\Core\Services\Caster\Contracts\CasterInterface;
use Concept\Core\Services\Caster\Exceptions\CastingException;
use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Services\Logger\Contracts\LoggerInterface;
use Concept\Core\Services\Validator\Contracts\ValidationInterface;
use Concept\Core\Services\Validator\Contracts\ValidatorInterface;
use Concept\Core\Services\Validator\ValidationTranslationsLoader;
use Concept\Core\Services\Validator\Exceptions\ValidationCastException;
use Concept\Core\Services\Validator\Exceptions\ValidationLogicException;
use Concept\Core\Services\Dto\Dto;
use Concept\Core\Http\RequestFormat;
use Concept\Core\Http\Requests\FormRequest;
use Concept\Core\Services\Session\SessionKey;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;

final class FormRequestTest extends TestCase
{
    public function testValidateBuildsValidationAndLogsWhenEnabled(): void
    {
        $request = (new ServerRequest())
            ->withUri(new Uri('https://app.test/posts'))
            ->withQueryParams(['page' => '2'])
            ->withParsedBody(['title' => 'Hello']);

        $validation = $this->createMock(ValidationInterface::class);
        $validation->expects(self::once())->method('setAliases')->with(['title' => 'Title']);
        $validation->expects(self::once())->method('setMessages')->with(['title:required' => 'Title is required']);
        $validation->expects(self::once())->method('validate');
        $validation->expects(self::once())->method('isValid')->willReturn(true);
        $validation->expects(self::once())->method('getValidData')->willReturn(['title' => 'Hello']);
        $validation->expects(self::once())->method('getErrors')->willReturn([]);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects(self::once())
            ->method('make')
            ->with(['page' => '2', 'title' => 'Hello'], ['title' => 'required'])
            ->willReturn($validation);

        $config = $this->createStub(ConfigInterface::class);
        $config->method('getBool')->willReturnOnConsecutiveCalls(false, true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'Validated data for [' . TestBlogFormRequest::class . ']',
                self::callback(static function (array $context): bool {
                    return $context['is_valid'] === true
                        && $context['valid_data'] === ['title' => 'Hello']
                        && $context['errors'] === [];
                })
            );

        $form = new TestBlogFormRequest(
            $request,
            new RequestFormat(),
            $config,
            $logger,
            $validator,
            $this->emptyTranslationsLoader(),
            null,
            ['title' => 'required'],
            ['title' => 'Title'],
            ['title:required' => 'Title is required']
        );

        self::assertTrue($form->validate());
    }

    public function testValidatedThrowsWhenValidateWasNotCalled(): void
    {
        $form = $this->makeRequestWithValidationData(['title' => 'x']);

        $this->expectException(ValidationLogicException::class);
        $form->validated();
    }

    public function testValidatedAppliesRulesOnlyExceptAndGlobalExcludes(): void
    {
        $form = $this->makeRequestWithValidationData([
            'title' => 'Hello',
            'email' => 'dev@app.test',
            SessionKey::CSRF_TOKEN => 'csrf',
            'not_in_rules' => 'drop',
        ]);

        $form->setOnlyFields(['title', 'email', SessionKey::CSRF_TOKEN]);
        $form->setExceptFields(['email']);

        $form->validate();

        self::assertSame(['title' => 'Hello'], $form->validated());
    }

    public function testAllMergesQueryAndBodyAndLogsInputWhenEnabled(): void
    {
        $request = (new ServerRequest())
            ->withUri(new Uri('https://app.test/filter'))
            ->withQueryParams(['q' => 'abc'])
            ->withParsedBody(['page' => '1']);

        $validation = $this->createStub(ValidationInterface::class);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('make')->willReturn($validation);

        $config = $this->createStub(ConfigInterface::class);
        $config->method('getBool')->willReturn(true);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'Validation incoming data [' . TestBlogFormRequest::class . ']',
                self::callback(static function (array $context): bool {
                    return $context['uri'] === '/filter'
                        && $context['data'] === ['q' => 'abc', 'page' => '1'];
                })
            );

        $form = new TestBlogFormRequest(
            $request,
            new RequestFormat(),
            $config,
            $logger,
            $validator,
            $this->emptyTranslationsLoader()
        );

        self::assertSame(['q' => 'abc', 'page' => '1'], $form->all());
    }

    public function testAllCastsObjectParsedBodyToArray(): void
    {
        $request = (new ServerRequest())
            ->withQueryParams(['q' => 'abc'])
            ->withParsedBody((object) ['page' => '1']);

        $form = $this->makeRequestWithValidationData([], request: $request);

        self::assertSame(['q' => 'abc', 'page' => '1'], $form->all());
    }

    public function testErrorsReturnValidationErrorsAfterValidation(): void
    {
        $validation = $this->createStub(ValidationInterface::class);
        $validation->method('isValid')->willReturn(false);
        $validation->method('getErrors')->willReturn(['title' => ['required']]);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('make')->willReturn($validation);

        $form = $this->makeRequestWithValidationData([], validator: $validator);
        $form->validate();

        self::assertSame(['title' => ['required']], $form->errors());
    }

    public function testToDtoReturnsNullWhenCasterOrDtoClassIsMissing(): void
    {
        $form = $this->makeRequestWithValidationData(['title' => 'Hello']);
        $form->validate();

        self::assertNull($form->toDto());
    }

    public function testToDtoReturnsNullWhenConfiguredDtoClassDoesNotExist(): void
    {
        $caster = $this->createStub(CasterInterface::class);

        $form = $this->makeRequestWithValidationData(['title' => 'Hello'], caster: $caster);
        $form->setDtoClassName('Tests\\Fixtures\\MissingDto');
        $form->validate();

        self::assertNull($form->toDto());
    }

    public function testToDtoReturnsMappedDtoWhenCasterConfigured(): void
    {
        $dto = new TestRequestDto();
        $dto->title = 'Mapped';


        $caster = $this->createMock(CasterInterface::class);
        $caster->expects(self::once())
            ->method('cast')
            ->with(['title' => 'Mapped'], TestRequestDto::class)
            ->willReturn($dto);

        $form = $this->makeRequestWithValidationData(['title' => 'Mapped'], caster: $caster);
        $form->setDtoClassName(TestRequestDto::class);
        $form->validate();

        self::assertSame($dto, $form->toDto());
    }

    public function testToDtoWrapsCastingException(): void
    {
        $caster = $this->createStub(CasterInterface::class);
        $caster->method('cast')->willThrowException(new CastingException(TestRequestDto::class));

        $form = $this->makeRequestWithValidationData(['title' => 'Mapped'], caster: $caster);
        $form->setDtoClassName(TestRequestDto::class);
        $form->validate();

        $this->expectException(ValidationCastException::class);
        $form->toDto();
    }

    public function testGetRouteParamViaSubclassWrapper(): void
    {
        $request = (new ServerRequest())->withAttribute('postId', 42);
        $form = $this->makeRequestWithValidationData([], request: $request);

        self::assertSame(42, $form->routeParam('postId'));
        self::assertSame('fallback', $form->routeParam('missing', 'fallback'));
    }

    public function testValidateAppliesTranslationsFromProviderBeforeRequestMessages(): void
    {
        $setMessagesCalls = [];
        $validation = $this->createMock(ValidationInterface::class);
        $validation->expects(self::exactly(2))
            ->method('setMessages')
            ->willReturnCallback(function (array $messages) use (&$setMessagesCalls): void {
                $setMessagesCalls[] = $messages;
            });
        $validation->expects(self::once())->method('setTranslations')->with(['or' => 'або']);
        $validation->expects(self::once())->method('validate');
        $validation->method('isValid')->willReturn(true);
        $validation->method('getValidData')->willReturn([]);
        $validation->method('getErrors')->willReturn([]);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('make')->willReturn($validation);

        $translations = $this->createStub(ValidationTranslationsLoader::class);
        $translations->method('resolve')->willReturn([
            'messages' => ['required' => 'Provider required'],
            'translations' => ['or' => 'або'],
            'aliases' => [],
        ]);

        $form = new TestBlogFormRequest(
            new ServerRequest(),
            new RequestFormat(),
            $this->createStub(ConfigInterface::class),
            $this->createStub(LoggerInterface::class),
            $validator,
            $translations,
            null,
            ['title' => 'required'],
            [],
            ['title:required' => 'Title is required']
        );

        $form->validate();

        self::assertSame([
            ['required' => 'Provider required'],
            ['title:required' => 'Title is required'],
        ], $setMessagesCalls);
    }

    public function testValidateMergesProviderAliasesWithRequestAliases(): void
    {
        $validation = $this->createMock(ValidationInterface::class);
        $validation->expects(self::once())->method('setAliases')->with([
            'email' => 'Email Address',
            'title' => 'Custom Title',
        ]);
        $validation->expects(self::once())->method('validate');
        $validation->method('isValid')->willReturn(true);
        $validation->method('getValidData')->willReturn([]);
        $validation->method('getErrors')->willReturn([]);

        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('make')->willReturn($validation);

        $translations = $this->createStub(ValidationTranslationsLoader::class);
        $translations->method('resolve')->willReturn([
            'messages' => [],
            'translations' => [],
            'aliases' => [
                'email' => 'Email Address',
                'title' => 'Title',
            ],
        ]);

        $form = new TestBlogFormRequest(
            new ServerRequest(),
            new RequestFormat(),
            $this->createStub(ConfigInterface::class),
            $this->createStub(LoggerInterface::class),
            $validator,
            $translations,
            null,
            ['title' => 'required'],
            ['title' => 'Custom Title']
        );

        $form->validate();
    }

    public function testDefaultAliasesAreEmpty(): void
    {
        $form = new TestPlainFormRequest(
            new ServerRequest(),
            new RequestFormat(),
            $this->createStub(ConfigInterface::class),
            $this->createStub(LoggerInterface::class),
            $this->createStub(ValidatorInterface::class),
            $this->emptyTranslationsLoader()
        );

        self::assertSame([], $form->aliases());
        self::assertSame([], $form->messages());
    }

    /**
     * @param array<string, mixed> $validData
     * @param array<string, string> $rules
     */
    private function makeRequestWithValidationData(
        array $validData,
        array $rules = ['title' => 'required', 'email' => 'required', SessionKey::CSRF_TOKEN => 'required'],
        ?LoggerInterface $logger = null,
        ?ConfigInterface $config = null,
        ?ValidatorInterface $validator = null,
        ?CasterInterface $caster = null,
        ?ServerRequest $request = null
    ): TestBlogFormRequest {
        $validation = $this->createStub(ValidationInterface::class);
        $validation->method('validate');
        $validation->method('isValid')->willReturn(true);
        $validation->method('getValidData')->willReturn($validData);
        $validation->method('getErrors')->willReturn([]);

        $validator ??= $this->createStub(ValidatorInterface::class);
        $validator->method('make')->willReturn($validation);

        $config ??= $this->createStub(ConfigInterface::class);
        $config->method('getBool')->willReturn(false);

        $logger ??= $this->createStub(LoggerInterface::class);

        $request ??= new ServerRequest();

        return new TestBlogFormRequest(
            $request,
            new RequestFormat(),
            $config,
            $logger,
            $validator,
            $this->emptyTranslationsLoader(),
            $caster,
            $rules,
            ['title' => 'Title']
        );
    }

    private function emptyTranslationsLoader(): ValidationTranslationsLoader
    {
        $loader = $this->createStub(ValidationTranslationsLoader::class);
        $loader->method('resolve')->willReturn([
            'messages' => [],
            'translations' => [],
            'aliases' => [],
        ]);

        return $loader;
    }
}

final class TestBlogFormRequest extends FormRequest
{
    /** @param array<string, string> $rules */
    public function __construct(
        ServerRequest $request,
        RequestFormat $requestFormat,
        ConfigInterface $config,
        LoggerInterface $logger,
        ValidatorInterface $validator,
        ValidationTranslationsLoader $translations,
        ?CasterInterface $caster = null,
        private array $rules = ['title' => 'required'],
        private array $aliases = [],
        private array $messages = [],
    ) {
        parent::__construct($request, $requestFormat, $config, $logger, $validator, $translations, $caster);
    }

    public function rules(): array
    {
        return $this->rules;
    }

    public function aliases(): array
    {
        return $this->aliases;
    }

    public function messages(): array
    {
        return $this->messages;
    }

    /** @param array<string> $fields */
    public function setOnlyFields(array $fields): void
    {
        $this->only = $fields;
    }

    /** @param array<string> $fields */
    public function setExceptFields(array $fields): void
    {
        $this->except = $fields;
    }

    public function setDtoClassName(?string $class): void
    {
        $this->dtoClass = $class;
    }

    public function routeParam(string $key, mixed $default = null): mixed
    {
        return $this->getRouteParam($key, $default);
    }
}

final class TestRequestDto extends Dto
{
    public string $title = '';
}

final class TestPlainFormRequest extends FormRequest
{
    public function __construct(
        ServerRequest $request,
        RequestFormat $requestFormat,
        ConfigInterface $config,
        LoggerInterface $logger,
        ValidatorInterface $validator,
        ValidationTranslationsLoader $translations,
    ) {
        parent::__construct($request, $requestFormat, $config, $logger, $validator, $translations);
    }

    public function rules(): array
    {
        return [];
    }
}
