<?php
// declare(strict_types=1); // XserverのPHPバージョンが古いためコメントアウト

function parse_email_list(string $raw): array {
  $raw = str_replace(["，","；","　"], [",",";"," "], $raw);
  $parts = preg_split('/[\s,;]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
  $out = [];
  foreach ($parts as $p) {
    $e = strtolower(trim($p));
    if ($e === '') continue;
    if (!filter_var($e, FILTER_VALIDATE_EMAIL)) continue; // MVP: invalids ignored
    $out[$e] = true;
  }
  return array_keys($out);
}
function dedupe_priority(array $to, array $cc, array $bcc): array {
  $warn = [];
  $toSet = array_fill_keys($to, true);
  $cc2 = [];
  foreach ($cc as $e) {
    if (!isset($toSet[$e])) $cc2[] = $e;
    else $warn[] = "CCから重複除去: $e";
  }
  $ccSet = array_fill_keys($cc2, true);
  $bcc2 = [];
  foreach ($bcc as $e) {
    if (!isset($toSet[$e]) && !isset($ccSet[$e])) $bcc2[] = $e;
    else $warn[] = "BCCから重複除去: $e";
  }
  return [$to, $cc2, $bcc2, $warn];
}
