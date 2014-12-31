<?php
echo $this->Rss->header();

if (!isset($channel)) {
    $channel = [];
}
if (!isset($channel['title'])) {
    $channel['title'] = $this->fetch('title');
}

echo $this->Rss->document(
    $this->Rss->channel(
        [], $channel, $this->fetch('content')
    )
);

?>
