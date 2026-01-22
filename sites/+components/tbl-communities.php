<?php
	/**
	 * \file
	 * Generate table of Communities.
	 */

	require_once '+components/table/table-fragment.php';

	/**
	 * @param CommunityRoom[] $rooms
	 */
	function renderCommunityRoomTable(array $rooms) {

		// Once handlers are attached in JS, this check ceases to be useful.
		function column_sortable($id) {
			// Join URL contents are not guaranteed to have visible text.
			return $id != "preview" && $id != "join_url";
		}

		function sort_onclick($column) {
			$name = isset($column['name_long']) ? $column['name_long'] : $column['name'];
			if (!column_sortable($column['id'])) return "title='$name'";
			$name = mb_strtolower($name);
			return "title='Click to sort by $name.'";
		}

		// Note: Changing the names or columns displayed requires updating
		// the --expanded-static-column-width and --collapsed-static-column-width CSS variables.

		$TABLE_COLUMNS = [
			['id' => "language", 'name' => "L", 'name_long' => "Language"],
			['id' => "name", 'name' => "Name"],
			['id' => "description", 'name' => "About", 'name_long' => "Description"],
			['id' => "users", 'name' => "#", 'name_long' => "Active Users"],
			['id' => "preview", 'name' => "Preview", 'name_long' => "Preview (external link)"],
			['id' => "server_icon", 'name' => "Host", 'name_long' => "Server host"],
			['id' => "join_url", 'name' => "URL", 'name_long' => "Join URL (for use in-app)"],
		];
	?>

	<table id="tbl_communities">
		<tr>
<?php foreach ($TABLE_COLUMNS as $column): ?>
			<th <?=sort_onclick($column)?> id="th_<?=$column['id']?>" class="tbl_communities__th">
				<?=$column['name']?>

			</th>
<?php endforeach; ?>
		</tr>
<?php renderCommunityRoomTableFragment($rooms); ?>
	</table>
<?php
}
?>
