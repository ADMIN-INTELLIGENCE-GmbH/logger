<?php

namespace App\Http\Requests\Api;

use App\Models\Log;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IngestLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller via X-Project-Key
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'level' => 'required|string|in:' . implode(',', Log::LEVELS),
            'message' => 'required|string|max:65535',
            'channel' => 'nullable|string|max:255',
            'datetime' => 'nullable|date',
            'context' => 'nullable|array',
            'extra' => 'nullable|array',
            'controller' => 'nullable|string|max:255',
            'controller_action' => 'nullable|string|max:255',
            'route_name' => 'nullable|string|max:255',
            'method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'request_method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'request_url' => 'nullable|string|max:65535',
            'user_id' => 'nullable|string|max:255',
            'ip_address' => 'nullable|string|max:45',
            'user_agent' => 'nullable|string|max:1024',
            'app_env' => 'nullable|string|max:50',
            'app_debug' => 'nullable|boolean',
            'referrer' => 'nullable|string|max:2048',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'level.required' => 'The log level is required.',
            'level.in' => 'The log level must be one of: ' . implode(', ', Log::LEVELS),
            'message.required' => 'The log message is required.',
            'message.max' => 'The log message may not exceed 65535 characters.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'error' => 'Validation Error',
            'message' => 'Invalid payload',
            'errors' => $validator->errors(),
        ], 422));
    }

    /**
     * Get the controller value, supporting both field names.
     */
    public function getLogController(): ?string
    {
        return $this->input('controller') ?? $this->input('controller_action');
    }

    /**
     * Get the HTTP method value, supporting both field names.
     */
    public function getHttpMethod(): ?string
    {
        return $this->input('method') ?? $this->input('request_method');
    }

    /**
     * Get the parsed datetime or null.
     */
    public function getLogDatetime(): ?\DateTimeInterface
    {
        $datetime = $this->input('datetime');
        if ($datetime) {
            try {
                return new \DateTimeImmutable($datetime);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get truncated context if too large.
     */
    public function getTruncatedContext(): ?array
    {
        $context = $this->input('context');
        if ($context && strlen(json_encode($context)) > 1048576) {
            return ['_truncated' => true, '_message' => 'Context too large, truncated'];
        }
        return $context;
    }

    /**
     * Get truncated extra if too large.
     */
    public function getTruncatedExtra(): ?array
    {
        $extra = $this->input('extra');
        if ($extra && strlen(json_encode($extra)) > 1048576) {
            return ['_truncated' => true, '_message' => 'Extra data too large, truncated'];
        }
        return $extra;
    }
}
