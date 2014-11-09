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
<%= $this->element('/Controller/Action/index') %>
<%= $this->element('/Controller/Action/view') %>
<%= $this->element('/Controller/Action/add') %>
<%= $this->element('/Controller/Action/edit') %>
<%= $this->element('/Controller/Action/delete') %>
