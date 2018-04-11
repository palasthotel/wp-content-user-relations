# Content User Relations

This is a plugin for WordPress that lets you build grouped relations between users and contents.

## Settings

First you have to setup the plugin. Goto User » Relations. There you have the following options.

### Post Types

Choose which post types should support building a relation to a user. All checked post types will be available for user relations.

### Types

You have at least to add one type of relation. These types will give user states context. If you click on a created state you get to a page where you can add available user states to that type of relation.

### States

You can add relation states which are flags that can be added for a user on a content. States have to be assigned to a relation type and can be assigned to multiple relation types.


## Backend

There are several posibilities to use Content User Relations in themes or other plugins.

### WP_User_Query

We provide new arguments for WP_User_Query. With the argument ```content_relations``` you can meta query users for relations.

Example:

```php
WP_User_Query(array(
	'role' => 'administrator' // core argument of WordPress
	'content_relations' => array( // new custom argument coming with this plugin
		'type_slug' => 'group',
		'state_slug' => 'speaker',
	) 
));
```

This will query for all users that have administrator role and the ```speaker``` state of the ```group``` type.

Advanced example:

```php
WP_User_Query(array(
	'role' => 'subscriber', // core argument of WordPress
	'content_relations' => array( // new custom argument coming with this plugin
		'relation' => 'OR',
		array(
			'type_slug' => 'group',
			'state_slug' => 'speaker',
		),
		array(
			'state_slug' => 'participant',
		)
	)
);
```

This query will query for all subscribers that have state ```speaker``` in ```group``` type or have the state ```participant``` in any type of relation. You probably remember this kind of syntax from WordPress meta queries. And it's pretty similar. You can interlace these queries however you want. 


### WP_Query

There are new arguments for WP_Query as well.

#### user_relatable

Queries only those post types that are whitelisted for relations in relation settings. This argument will only have an effect with value ```true```.

```php
new WP_Query(array(
	'user_relatable' => true,
));
```

#### related\_to\_user

You try to find which contents are related to a user? No prob! Use this arguement with the user_id and you'll get all contents that are related.

```php
new WP_Query(array(
	'related_to_user' => 20,
));
```

### ContentUserRelationsQuery

This is a separate query class which can get you relations from database. Relations are always object with the structure:

```
{
	id: int, // id of relation
	post_id: int, // post_id of content
	user_id: int, // id of user
	type_id: int, // id of type
	type_name: string, // human readable name of group
	type_slug: string // maschine readable name of group
	state_id: int, // id of state
	state_name: string, // human readable name of state
	state_slug: string, // maschine readable name of state
}

```

#### Examples

All relations to content with ```post_id``` ```20```.

```php
$query = new ContentUserRelationsQuery(array(
	'post_id' => 20,
));
$query->get();
```

All relations of the user with id ```10```.

```php
$query = new ContentUserRelationsQuery(array(
	'user_id' => 10,
));
$query->get();
```

All relations of user ```10``` to post with ```post_id``` ```20```.

```php
$query = new ContentUserRelationsQuery(array(
	'user_id'=>10,
	'post_id' => 20,
));
$query->get();
```

#### Special args

There are some special arguments for ContentUserRelationsQuery. If you use one of those, all other arguments will be skipped. The result is always a list of objects with the following structure:

```
{
	id: int, // the typestate id
	type_id: int, // it of type
	type_name: string, // human readable name of group
	type_slug: string, // maschine readable name of group
	state_id: int, // id of state
	state_name: string, // human readable name of state
	state_slug: string, // maschine readable name of state
}
```

Get a list of all available relations (type state combinations)

```php
$query = new ContentUserRelationsQuery(array(
	'list' => true,
));
$query->get();
```

Get all available relations for type

```php
$query = new ContentUserRelationsQuery(array(
	'for_type' => 2, // id of type
));
$query->get();
```

Get all available relations for state

```php
$query = new ContentUserRelationsQuery(array(
	'for_state' => 3, // id of state
));
$query->get();
```


## Public Functions

There are some public functions available. Please always check if function exists, so your code won't explode im this plugin is deactivated.

__Add relation__

```php
$false_or_1 = content_user_relations_add_relation(
	$user_id, // WP_User->ID
	$post_id, // WP_Post->ID
	$relation_type_slug, // slug string of relation type
	$relation_state_slug // slug string of relation state
)

```

__Remove relation__

```php
$success_bool = content_user_relations_remove_relation(
	$user_id, // WP_User->ID
	$post_id, // WP_Post->ID
	$relation_type_slug, // slug string of relation type
	$relation_state_slug // slug string of relation state
)
```