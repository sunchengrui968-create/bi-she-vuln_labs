<?php
return [
    'script' => "preview.innerHTML = payload;",
    'defense_note' => 'Low 将 URL 数据直接写入 innerHTML。',
];
