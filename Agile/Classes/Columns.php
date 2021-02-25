<?php

namespace Agile;

class Column{
	/** @var string COLUMN the name of the database column */
	const COLUMN = 'column';
	/** @var Boolean for indicating a json column. Table insert functions will attempt to json_encode your data */
	const JSON = 'json';
	/** @var string TYPE the database column type (text,varchar,bit,int) */
	const TYPE = 'type';
	/** @var string LABEL a nice, human-readable label for the column. Used for grid headers and reports */
	const LABEL = 'label';

	const ALLOW_BLANK = 'allowBlank';

	const MAX_LENGTH = 'maxLength';

	//Default True
	const REQUIRED_CREATE = 'requiredCreate';
	const USED_CREATE = 'usedCreate';
	const REQUIRED_UPDATE = 'requiredUpdate';
	const USED_UPDATE = 'usedUpdate';
	//Default False
	const REQUIRED_DELETE = 'requiredDelete';

	//Default False
	const EXCLUDE_FROM_CHANGELOG= 'excludeFromChangeLog';

	//Default True
	const UPPERCASE = 'upperCase';

	const FORMAT_FUNCTION = 'formatFunction';

	/** @var string GRID_COLUMN_WIDTH defines how wide a sencha grid column should be to display this column */
	const GRID_COLUMN_WIDTH = 'gridColumnWidth';

	/** @var string GRID_COLUMN_WIDTH defines if sencha grid column should be hidden */
	const GRID_COLUMN_HIDDEN = 'gridColumnHidden';
}