<?php declare(strict_types=1);

namespace Concept\Core\Http\Requests;

interface FormRequestInterface
{
    /**
     * @return array<string, string[]>
     */
    public function rules(): array;

    /**
     * @return array<string, string>
     */
    public function aliases(): array;

    public function validate(): bool;

    /**
     * @return array<mixed>
     */
    public function validated(): array;

    /**
     * @return array<string, array<string>>
     */
    public function errors(): array;

    /**
     * @return array<string, mixed>
     */
    public function all(): array;
}