<?php
/**
 * 1218行目付近の構文エラーを確認するスクリプト
 * 括弧のバランスをチェックします
 */

$file = __DIR__ . '/public/index.php';
if (!file_exists($file)) {
    die("File not found: $file\n");
}

$content = file_get_contents($file);
$lines = explode("\n", $content);

echo "=== 1218行目付近のコード確認 ===\n\n";

// 1210-1225行目を表示
for ($i = 1209; $i < 1225 && $i < count($lines); $i++) {
    $lineNum = $i + 1;
    $line = $lines[$i];
    echo sprintf("%4d: %s\n", $lineNum, $line);
}

echo "\n=== 括弧のバランスチェック ===\n\n";

// 1166行目から1243行目までの括弧をチェック
$startLine = 1165; // 0-based index
$endLine = 1242;   // 0-based index

$openParen = 0;
$closeParen = 0;
$openBracket = 0;
$closeBracket = 0;
$openBrace = 0;
$closeBrace = 0;

for ($i = $startLine; $i <= $endLine && $i < count($lines); $i++) {
    $line = $lines[$i];
    $lineNum = $i + 1;
    
    // 括弧をカウント
    $openParen += substr_count($line, '(');
    $closeParen += substr_count($line, ')');
    $openBracket += substr_count($line, '[');
    $closeBracket += substr_count($line, ']');
    $openBrace += substr_count($line, '{');
    $closeBrace += substr_count($line, '}');
    
    // 1218行目付近で特にチェック
    if ($lineNum >= 1210 && $lineNum <= 1225) {
        $parenDiff = $openParen - $closeParen;
        $bracketDiff = $openBracket - $closeBracket;
        $braceDiff = $openBrace - $closeBrace;
        
        if ($parenDiff < 0 || $bracketDiff < 0 || $braceDiff < 0) {
            echo "WARNING: Line $lineNum - Unbalanced brackets detected!\n";
            echo "  Paren: $openParen open, $closeParen close (diff: $parenDiff)\n";
            echo "  Bracket: $openBracket open, $closeBracket close (diff: $bracketDiff)\n";
            echo "  Brace: $openBrace open, $closeBrace close (diff: $braceDiff)\n";
        }
    }
}

echo "\n=== 最終結果 ===\n";
echo "開き括弧 '(': $openParen\n";
echo "閉じ括弧 ')': $closeParen\n";
echo "差分: " . ($openParen - $closeParen) . "\n";
echo "\n";
echo "開き角括弧 '[': $openBracket\n";
echo "閉じ角括弧 ']': $closeBracket\n";
echo "差分: " . ($openBracket - $closeBracket) . "\n";
echo "\n";
echo "開き波括弧 '{': $openBrace\n";
echo "閉じ波括弧 '}': $closeBrace\n";
echo "差分: " . ($openBrace - $closeBrace) . "\n";

if ($openParen !== $closeParen) {
    echo "\nERROR: 括弧 '(' と ')' の数が一致しません！\n";
}
if ($openBracket !== $closeBracket) {
    echo "\nERROR: 角括弧 '[' と ']' の数が一致しません！\n";
}
if ($openBrace !== $closeBrace) {
    echo "\nERROR: 波括弧 '{' と '}' の数が一致しません！\n";
}

if ($openParen === $closeParen && $openBracket === $closeBracket && $openBrace === $closeBrace) {
    echo "\n✓ 括弧のバランスは正しいです。\n";
}

echo "\n=== PHP構文チェック ===\n";
$output = [];
$returnVar = 0;
exec("php -l \"$file\" 2>&1", $output, $returnVar);
echo implode("\n", $output) . "\n";

if ($returnVar === 0) {
    echo "\n✓ PHP構文チェック: エラーなし\n";
} else {
    echo "\n✗ PHP構文チェック: エラーが検出されました\n";
}
