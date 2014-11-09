$extractor = function ($val) {
	return $val->target()->alias();
};

$belongsTo = array_map($extractor, $modelObj->associations()->type('BelongsTo'));
$belongsToMany = array_map($extractor, $modelObj->associations()->type('BelongsToMany'));

$editAssociations = array_merge($belongsTo, $belongsToMany);

$allAssociations = array_merge(
	$editAssociations,
	array_map($extractor, $modelObj->associations()->type('HasOne')),
	array_map($extractor, $modelObj->associations()->type('HasMany'))
);
%>
<%= $this->render('/Controller/Action/index') %>
<%= $this->render('/Controller/Action/view') %>
<%= $this->render('/Controller/Action/add') %>
<%= $this->render('/Controller/Action/edit') %>
<%= $this->render('/Controller/Action/delete') %>
