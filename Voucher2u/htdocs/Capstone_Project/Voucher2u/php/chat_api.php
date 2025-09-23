    $tools = [
        [
            'tool' => [
                'function_declaration' => [
                    'name' => 'get_available_vouchers',
                    'description' => 'Gets a list of available vouchers in the loyalty program.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                    ],
                ],
            ],
        ],
        [
            'tool' => [
                'function_declaration' => [
                    'name' => 'get_user_vouchers',
                    'description' => 'Gets a list of vouchers redeemed by a specific user.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'userId' => [
                                'type' => 'integer',
                                'description' => 'The ID of the user.',
                            ],
                        ],
                        'required' => ['userId'],
                    ],
                ],
            ],
        ],
    ];
