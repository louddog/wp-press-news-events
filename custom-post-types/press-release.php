<?php

new PNE_Press_Release;
class PNE_Press_Release extends PNE_Custom_Post_Type {
	var $slug = 'press-release';
	var $archive_slug = 'press-releases';
	var $singular = "Press Release";
	var $plural = "Press Releases";
}