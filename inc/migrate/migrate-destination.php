<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 07.02.18
 * Time: 13:10
 */

namespace ContentUserRelations;


class MigrateDestination extends \ph_destination {

	// create
	public function createItem() {
		return new \stdClass();
	}

	// update and delete
	public function getItemByID( $id ) {
		return Database\getRelationById( $id );
	}

	// update and create
	public function save( $item ) {

		$typestate_id = Database\getTypeStateId(
			$item->type_slug,
			$item->state_slug
		);

		// If there's an Id, the Relation already exists and is updated
		if(isset($item->id)){
			$id = $item->id;

			if($typestate_id != $item->typestate_id){
				Database\updateRelation(
					$id,
					$item->user_id,
					$item->post_id,
					$typestate_id
				);
			}
		} else {
			// If there's no item
			$relation_id = Database\getRelationId($item->user_id, $item->post_id, $typestate_id);
			if($relation_id != null){
				return null;
			}
			$id = Database\addRelation(
				$item->user_id,
				$item->post_id,
				$item->type_slug,
				$item->state_slug
			);
			if(false === $id) return null;
		}

		return $id;
	}

	// delete
	public function deleteItem( $item ) {
		Database\removeRelation(
			$item->user_id,
			$item->post_id,
			$item->type_slug,
			$item->state_slug
		);
	}
}