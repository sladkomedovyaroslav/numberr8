<?php
class Validator {
    // Единые правила валидации (используются и в API, и в форме)
    private const RULES = [
        'full_name' => [
            'required' => true,
            'pattern' => '/^[a-zA-Zа-яА-ЯёЁ\s\-]{1,150}$/u',
            'message' => 'ФИО должно содержать только буквы, пробелы и дефисы (не более 150 символов)'
        ],
        'phone' => [
            'required' => true,
            'pattern' => '/^[\d\s\+\(\)-]{5,20}$/',
            'message' => 'Телефон должен содержать только цифры, пробелы, +, (, ), - (5-20 символов)'
        ],
        'email' => [
            'required' => true,
            'filter' => FILTER_VALIDATE_EMAIL,
            'message' => 'Введите корректный email'
        ],
        'birth_date' => [
            'required' => true,
            'custom' => 'validateBirthDate',
            'message' => 'Дата рождения не может быть в будущем'
        ],
        'gender' => [
            'required' => true,
            'values' => ['male', 'female'],
            'message' => 'Выберите пол'
        ],
        'languages' => [
            'required' => true,
            'custom' => 'validateLanguages',
            'message' => 'Выберите хотя бы один язык программирования'
        ],
        'agreed' => [
            'required' => true,
            'values' => ['1', 1, true],
            'message' => 'Вы должны согласиться с условиями'
        ]
    ];
    
    // Допустимые языки программирования
    private const ALLOWED_LANGUAGES = [
        'Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python',
        'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'
    ];
    
    /**
     * Валидация данных формы
     * @return array Массив ошибок (пустой, если ошибок нет)
     */
    public static function validate(array $data): array {
        $errors = [];
        
        foreach (self::RULES as $field => $rules) {
            $value = $data[$field] ?? null;
            
            // Проверка на обязательность
            if (!empty($rules['required']) && empty($value) && $value !== '0') {
                $errors[$field] = $rules['message'];
                continue;
            }
            
            if (empty($value) && $value !== '0') {
                continue;
            }
            
            // Проверка по регулярному выражению
            if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
                $errors[$field] = $rules['message'];
                continue;
            }
            
            // Проверка через фильтр
            if (isset($rules['filter']) && !filter_var($value, $rules['filter'])) {
                $errors[$field] = $rules['message'];
                continue;
            }
            
            // Проверка по списку допустимых значений
            if (isset($rules['values']) && !in_array($value, $rules['values'], true)) {
                $errors[$field] = $rules['message'];
                continue;
            }
            
            // Пользовательская проверка
            if (isset($rules['custom']) && method_exists(self::class, $rules['custom'])) {
                if (!self::{$rules['custom']}($value)) {
                    $errors[$field] = $rules['message'];
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Проверка даты рождения (не в будущем)
     */
    private static function validateBirthDate(string $value): bool {
        $timestamp = strtotime($value);
        return $timestamp !== false && $timestamp <= time();
    }
    
    /**
     * Проверка выбранных языков
     */
    private static function validateLanguages(mixed $value): bool {
        if (!is_array($value) || empty($value)) {
            return false;
        }
        foreach ($value as $lang) {
            if (!in_array($lang, self::ALLOWED_LANGUAGES, true)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Получить правила валидации для клиентской стороны (JSON)
     */
    public static function getRulesForClient(): array {
        $clientRules = [];
        foreach (self::RULES as $field => $rules) {
            $clientRules[$field] = [
                'required' => $rules['required'] ?? false,
                'pattern' => $rules['pattern'] ?? null,
                'message' => $rules['message'] ?? ''
            ];
        }
        return $clientRules;
    }
}