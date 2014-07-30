<?php
/**
 * ElggNotification view.
 *
 * @package Notifier
 */

$notification = $vars['entity'];

// Ignore access to make e.g. a hidden group visible in membership invitation
// TODO Find an alternate approach that doesn't risk security
$ia = elgg_set_ignore_access(true);
$target = $notification->getTarget();
elgg_set_ignore_access($ia);

$subjects = $notification->getSubjects();
$subject = $notification->getSubject();

if (!$target || empty($subjects)) {
	// Add admin notice to help trace the reason of invalid notifications
	$title = $notification->title;
	$event = $notification->event;
	$subject = $subject->username;
	$user = $notification->getOwnerEntity()->username;
	$notice = "Failed to view notification $title ($event) from user $subject to user $user";
	elgg_add_admin_notice('notifier_no_target', $notice);

	// The entity to notify about doesn't exist anymore so delete the notification
	$notification->delete();
	return false;
}

$vars['target'] = $target;
$vars['subject'] = $subject;
$vars['subjects'] = $subjects;
$vars['notification'] = $notification;

$event_view = str_replace(':', '/', $notification->event);
$view = "notifier/messages/$event_view";

if (elgg_view_exists($view)) {
	// Use special view for this notification type
	$subtitle = elgg_view($view, $vars);
} else {
	$subtitle = elgg_view('notifier/messages/create/default', $vars);
}

$time = elgg_view_friendly_time($notification->time_created);

if (elgg_in_context('widgets')) {
	// Do not show the delete link in widget view
	$metadata = '';
} else {
	// Use link instead of entity menu since we don't want any links besides delete
	$metadata = elgg_view('output/confirmlink', array(
		'name' => 'delete',
		'href' => "action/notifier/delete?guid={$notification->getGUID()}",
		'text' => elgg_view_icon('delete'),
		'class' => 'float-alt',
	));
}

$icon = elgg_view_entity_icon($subject, 'tiny');

if ($notification->status === 'unread') {
	$vars['class'] = 'elgg-notifier-unread';
}

$params = array(
	'entity' => $notification,
	'title' => false,
	'metadata' => $metadata,
	'subtitle' => "$subtitle $time",
);
$params = $params + $vars;
$body = elgg_view('object/elements/summary', $params);

echo elgg_view_image_block($icon, $body, $vars);
