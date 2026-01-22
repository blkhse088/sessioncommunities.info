<?php
	require_once 'community-row.php';

	/**
	 * @param CommunityRoom[] $rooms
	 */
	function renderCommunityRoomTableFragment(array $rooms) {
		foreach ($rooms as $room) {
			renderCommunityRoomRow($room);
		}
	}
?>
