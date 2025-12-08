<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | Your OpenAI API key for authentication.
    |
    */
    'api_key' => env('OPENAI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Project ID
    |--------------------------------------------------------------------------
    |
    | Your OpenAI Project ID for organization.
    |
    */
    'project_id' => env('OPENAI_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Model
    |--------------------------------------------------------------------------
    |
    | The OpenAI model to use for log analysis.
    | Examples: gpt-4.1-nano, gpt-4.1-mini
    |
    */
    'model' => env('OPENAI_MODEL', 'gpt-4.1-nano'),

];
