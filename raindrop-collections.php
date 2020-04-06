<?php
// Script for getting and rendering Raindrop.io collections for Alfred,
// and preparing for adding a new bookmark
//
// By Andreas Westerlind in 2020
//

require './WorkflowsPHPHelper/src/Workflow.php';
require './WorkflowsPHPHelper/src/Result.php';
require 'raindrop-common.php';
use Alfred\Workflows\Workflow;

$query = $argv[2];
if (substr($argv[1], 0, 16) === "{\"alfredworkflow") {
  $firefox_tab = json_decode($argv[1], true);
  $browserUrl = $firefox_tab["alfredworkflow"]["variables"]["FF_URL"]; 
  $browserTitle = $firefox_tab["alfredworkflow"]["variables"]["FF_TITLE"];
}
else {
  $browserUrl = $argv[1];
  $browserTitle = $argv[3];
}
$workflow = new Workflow;

// Check if the token file exists and otherwise send the user over to the authentication
if (!file_exists("token.json")) {
  init_auth($workflow);
}

// Read token and related data from file
$token = json_decode(file_get_contents("token.json"), true);

if ($query == "") {
  // Check if Token has gone through more than half of it's lifetime, and in that case, refresh it
  $current_time = new DateTime("now", new DateTimeZone('UTC'));
  $token_time = date_create_from_format("Y-m-d H:i:s", $token["creation_time"], new DateTimeZone('UTC'));
  $time_difference = $token_time->diff($current_time);
  if ((int)$token["expires"] - date_interval_to_milliseconds($time_difference) < (int)$token["expires"] * 0.5) {
    refresh_token($token["refresh_token"]);
  }
}

if ($browserUrl === "No browser active") {
  // Result we didn't get any URL to save, probably because no browser is the frontmost app
  $workflow->result()
    ->valid(false)
    ->title("There is nothing here to add to Raindrop.io")
    ->subtitle("Go to the browser you want to add a bookmark from and try again");
  echo $workflow->output();
  die();
}

// Put alternative to add the new bookmark to Unsorted above the collection list
$workflow->result()
  ->arg("")
  ->mod('cmd', $sub_indentation . "Open Raindrop.io to change details after saving", " :§:open_raindrop:§: ")
  ->title("Add Raindrop.io Bookmark to Unsorted")
  ->subtitle("Or select a collection below");

// Make sure that the icon_cache directory exists
if (!file_exists('icon_cache')) {
  mkdir('icon_cache', 0777, true);
}

// Get collections
$raindrop_collections = array_reverse(collections($token["access_token"], false)["items"]);
$raindrop_collections_sublevel = array_reverse(collections($token["access_token"], true)["items"]);

// Render collections
render_collections($raindrop_collections, $raindrop_collections_sublevel, $workflow, "tree", "adding");

// Add Alfred variable for the URL we want to add to Raindrop
$workflow->variable('url', $browserUrl);
$workflow->variable('title', $browserTitle);

// Output to Alfred
if ($query == "") {
  //echo $workflow->sortResults('asc', 'uid')->output();
  echo $workflow->output();
} else {
  //echo $workflow->filterResults(mb_strtolower($query), 'uid')->sortResults('asc', 'uid')->output();
  echo $workflow->filterResults(mb_strtolower($query), 'arg')->output();
}