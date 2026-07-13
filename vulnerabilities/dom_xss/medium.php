<?php
$script = <<<'JAVASCRIPT'
payload = payload
    .replace(/<\/?script[^>]*>/gi, '')
    .replace(/javascript:/gi, '')
    .replace(/alert\s*\(/gi, 'blocked(');
preview.innerHTML = payload;
JAVASCRIPT;

return [
    'script' => $script,
    'defense_note' => 'Medium 删除少量 script、javascript: 与 alert 片段，但最终仍使用 innerHTML。',
];
