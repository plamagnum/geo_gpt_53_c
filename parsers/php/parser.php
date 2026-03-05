<?php
/**
 * Простий парсер тестів на PHP.
 *
 * Щоб адаптувати під інший сайт, достатньо змінити CSS селектори нижче.
 * Результат зберігається у JSON-файл.
 */

$source = $argv[1] ?? '';
$output = $argv[2] ?? __DIR__ . '/../../data/php_parsed_questions.json';

if ($source === '') {
    fwrite(STDERR, "Використання: php parser.php <url_or_html_file> [output_json]\n");
    exit(1);
}

// Налаштовувані селектори для HTML-структури.
$config = [
    'questionSelector' => 'div.question',
    'optionSelector' => 'li.option',
    'correctAttribute' => 'data-correct',
];

$html = str_starts_with($source, 'http://') || str_starts_with($source, 'https://')
    ? @file_get_contents($source)
    : @file_get_contents(realpath($source));

if (!$html) {
    fwrite(STDERR, "Не вдалося завантажити джерело: {$source}\n");
    exit(1);
}

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

$toXpath = function (string $selector): string {
    // Мінімальна підтримка селекторів виду tag.class.
    if (strpos($selector, '.') !== false) {
        [$tag, $class] = explode('.', $selector, 2);
        return "//{$tag}[contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')]";
    }
    return "//{$selector}";
};

$questionNodes = $xpath->query($toXpath($config['questionSelector']));
$result = [
    'class' => '7',
    'subject' => 'Невизначено',
    'topic' => 'Імпортовано парсером PHP',
    'test_title' => 'Імпорт із сайту (PHP parser)',
    'questions' => [],
];

foreach ($questionNodes as $questionNode) {
    $questionText = trim($questionNode->textContent);

    $optionNodes = $xpath->query('.//' . trim(str_replace('//', '', $toXpath($config['optionSelector']))), $questionNode);
    $options = [];
    $correctIndex = 0;

    foreach ($optionNodes as $index => $optionNode) {
        $options[] = trim($optionNode->textContent);
        if ($optionNode->getAttribute($config['correctAttribute']) === '1') {
            $correctIndex = $index;
        }
    }

    if (count($options) === 4) {
        $result['questions'][] = [
            'text' => $questionText,
            'options' => $options,
            'correct_index' => $correctIndex,
        ];
    }
}

file_put_contents($output, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Збережено у {$output}\n";
