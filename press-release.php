<?php

new LDWPPR_PressRelease;
class LDWPPR_PressRelease extends LDWPPR_CustomPostType {
	var $slug = 'press-release';
	var $archive_slug = 'press-releases';
	var $singular = "Press Release";
	var $plural = "Press Releases";
}