<?php

require("Differ.php");

function processUploadedFile($fileInfo) {
	$uploadDirectory = $_SERVER["DOCUMENT_ROOT"]."/diff/upload/";
	if (!file_exists($uploadDirectory)) {
		mkdir($uploadDirectory, 0777);
	}

	$srcPath = $fileInfo["tmp_name"];
	$tgtPath = $uploadDirectory.'/'.$fileInfo["name"];
	if (is_uploaded_file($srcPath)) {
		move_uploaded_file($srcPath, $tgtPath);
		$text = file($tgtPath, FILE_IGNORE_NEW_LINES);
	}
	return $text;
}

$linewise_texts = ["old" => [], "new" => []];
foreach (array_keys($linewise_texts) as $prefix) {
	$fileKey = $prefix."File";
	$textKey = $prefix."Text";
	$linewise_text = "";
	if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]["error"] === UPLOAD_ERR_OK) {
		$linewise_text = processUploadedFile($_FILES[$fileKey]);
	}
	else if (isset($_POST[$textKey])) {
		$linewise_text = explode("\n", $_POST[$textKey]);
	}
	else {
		echo "ERROR".PHP_EOL;
	}
	$linewise_texts[$prefix] = $linewise_text;
}

$differ = new Differ($linewise_texts["old"], $linewise_texts["new"]);
$response = $differ->diff(Differ::HTML_OUTPUT);
echo $response;
