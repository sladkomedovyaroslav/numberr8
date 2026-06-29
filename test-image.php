<?php
$heroPath = 'public/images/hero-bg.jpg';
$interiorPath = 'public/images/interior.jpg';

echo "<h2>Проверка файлов:</h2>";

if (file_exists($heroPath)) {
    echo "✅ hero-bg.jpg НАЙДЕН: " . realpath($heroPath) . "<br>";
    echo "Размер: " . filesize($heroPath) . " байт<br>";
    echo "<img src='$heroPath' width='300'><br>";
} else {
    echo "❌ hero-bg.jpg НЕ НАЙДЕН по пути: $heroPath<br>";
}

echo "<hr>";

if (file_exists($interiorPath)) {
    echo "✅ interior.jpg НАЙДЕН: " . realpath($interiorPath) . "<br>";
    echo "Размер: " . filesize($interiorPath) . " байт<br>";
    echo "<img src='$interiorPath' width='300'><br>";
} else {
    echo "❌ interior.jpg НЕ НАЙДЕН по пути: $interiorPath<br>";
}
?>