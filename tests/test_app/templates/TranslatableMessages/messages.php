<?php
use Cake\I18n\TranslatableMessage as Message;

__('Shared message');
new Message('Shared message');
new Message('Article title', domain: 'forms', context: 'heading');
new \Cake\I18n\TranslatableMessage("A \"quoted\" title\nNext line", domain: 'forms');
new Message('Hello {name}', arguments: ['name' => neverExecute()]);
new Message('0');
new Message('');
new Message('Unresolved domain', domain: $domain);

#[Example(label: new Message('Attribute label', domain: 'forms'))]
function messageAttribute(): void
{
    throw new RuntimeException('Extraction must not execute this source.');
}

throw new RuntimeException('Extraction must not execute this source.');
