<?php
// src/Models/Tool.php

abstract class Tool {
    abstract public function getName(): string;
    abstract public function getDescription(): string;
    abstract public function getParametersSchema(): array;
    abstract public function execute(array $parameters): array;
    
    /**
     * Get the OpenAI function definition for this tool
     */
    public function getOpenAIDefinition(): array {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => $this->getDescription(),
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->getParametersSchema(),
                    'required' => $this->getRequiredParameters()
                ]
            ]
        ];
    }
    
    /**
     * Get required parameters from the schema
     */
    protected function getRequiredParameters(): array {
        $required = [];
        foreach ($this->getParametersSchema() as $param => $config) {
            if (isset($config['required']) && $config['required']) {
                $required[] = $param;
            }
        }
        return $required;
    }
    
    /**
     * Validate parameters before execution
     */
    public function validateParameters(array $parameters): bool {
        $schema = $this->getParametersSchema();
        $required = $this->getRequiredParameters();
        
        // Check required parameters
        foreach ($required as $param) {
            if (!isset($parameters[$param])) {
                throw new InvalidArgumentException("Missing required parameter: {$param}");
            }
        }
        
        // Basic type validation
        foreach ($parameters as $param => $value) {
            if (isset($schema[$param]['type'])) {
                $expectedType = $schema[$param]['type'];
                $actualType = gettype($value);
                
                // Simple type checking
                if ($expectedType === 'string' && !is_string($value)) {
                    throw new InvalidArgumentException("Parameter {$param} must be a string");
                }
                if ($expectedType === 'integer' && !is_int($value)) {
                    throw new InvalidArgumentException("Parameter {$param} must be an integer");
                }
                if ($expectedType === 'boolean' && !is_bool($value)) {
                    throw new InvalidArgumentException("Parameter {$param} must be a boolean");
                }
            }
        }
        
        return true;
    }
    
    /**
     * Safe execution with validation and error handling
     */
    public function safeExecute(array $parameters): array {
        try {
            $this->validateParameters($parameters);
            return $this->execute($parameters);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tool' => $this->getName()
            ];
        }
    }
}