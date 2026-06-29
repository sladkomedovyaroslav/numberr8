<?php
class Template {
    private string $templateDir;
    
    public function __construct(string $templateDir = 'templates') {
        $this->templateDir = $templateDir;
    }
    
    /**
     * Рендеринг шаблона с передачей переменных
     */
    public function render(string $template, array $data = []): string {
        $templatePath = $this->templateDir . '/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            throw new RuntimeException("Шаблон не найден: {$template}");
        }
        
        // Извлекаем переменные в область видимости
        extract($data);
        
        // Буферизация вывода
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
}