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
        // Check if this is a batch request (array of logs) or single log
        if ($this->isBatchRequest()) {
            // Check if logs are wrapped in {"logs": [...]} or sent as raw array
            $isWrapped = $this->has('logs');
            $prefix = $isWrapped ? 'logs.*.' : '*.';
            
            return [
                $isWrapped ? 'logs' : '*' => 'required|array|min:1|max:100',
                $prefix.'level' => 'required|string|in:'.implode(',', Log::LEVELS),
                $prefix.'message' => 'required|string|max:65535',
                $prefix.'channel' => 'nullable|string|max:255',
                $prefix.'datetime' => 'nullable|date',
                $prefix.'context' => 'nullable|array',
                $prefix.'extra' => 'nullable|array',
                $prefix.'controller' => 'nullable|string|max:255',
                $prefix.'controller_action' => 'nullable|string|max:255',
                $prefix.'route_name' => 'nullable|string|max:255',
                $prefix.'method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
                $prefix.'request_method' => 'nullable|string|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
                $prefix.'request_url' => 'nullable|string|max:65535',
                $prefix.'user_id' => 'nullable|string|max:255',
                $prefix.'ip_address' => 'nullable|string|max:45',
                $prefix.'user_agent' => 'nullable|string|max:1024',
                $prefix.'app_env' => 'nullable|string|max:50',
                $prefix.'app_debug' => 'nullable|boolean',
                $prefix.'referrer' => 'nullable|string|max:2048',
            ];
        }

        // Single log validation
        return [
            'level' => 'required|string|in:'.implode(',', Log::LEVELS),
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
     * Check if this is a batch request.
     */
    public function isBatchRequest(): bool
    {
        // Check if the entire payload is an array (raw JSON array)
        $input = $this->all();
        
        // If the input is a sequential array with numeric keys, it's a batch
        if (is_array($input) && array_keys($input) === range(0, count($input) - 1)) {
            return true;
        }
        
        // Also support wrapped format {"logs": [...]}
        return $this->has('logs') && is_array($this->input('logs'));
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logs.required' => 'The logs array is required for batch requests.',
            'logs.min' => 'At least one log entry is required.',
            'logs.max' => 'Cannot process more than 100 logs at once.',
            'level.required' => 'The log level is required.',
            'level.in' => 'The log level must be one of: '.implode(', ', Log::LEVELS),
            'logs.*.level.required' => 'The log level is required for each log entry.',
            'logs.*.level.in' => 'The log level must be one of: '.implode(', ', Log::LEVELS),
            'message.required' => 'The log message is required.',
            'message.max' => 'The log message may not exceed 65535 characters.',
            'logs.*.message.required' => 'The log message is required for each log entry.',
            'logs.*.message.max' => 'The log message may not exceed 65535 characters.',
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
    public function getLogController(?array $logData = null): ?string
    {
        if ($logData !== null) {
            return $logData['controller'] ?? $logData['controller_action'] ?? null;
        }
        return $this->input('controller') ?? $this->input('controller_action');
    }

    /**
     * Get the HTTP method value, supporting both field names.
     */
    public function getHttpMethod(?array $logData = null): ?string
    {
        if ($logData !== null) {
            return $logData['method'] ?? $logData['request_method'] ?? null;
        }
        return $this->input('method') ?? $this->input('request_method');
    }

    /**
     * Get the parsed datetime or null.
     */
    public function getLogDatetime(?array $logData = null): ?\DateTimeInterface
    {
        $datetime = $logData !== null ? ($logData['datetime'] ?? null) : $this->input('datetime');
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
    public function getTruncatedContext(?array $logData = null): ?array
    {
        $context = $logData !== null ? ($logData['context'] ?? null) : $this->input('context');
        if ($context && strlen(json_encode($context)) > 1048576) {
            return ['_truncated' => true, '_message' => 'Context too large, truncated'];
        }

        return $context;
    }

    /**
     * Get truncated extra if too large.
     */
    public function getTruncatedExtra(?array $logData = null): ?array
    {
        $extra = $logData !== null ? ($logData['extra'] ?? null) : $this->input('extra');
        if ($extra && strlen(json_encode($extra)) > 1048576) {
            return ['_truncated' => true, '_message' => 'Extra data too large, truncated'];
        }

        return $extra;
    }
}
