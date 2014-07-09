<?php
class wallabag extends Plugin {

	private $host;

	function about() {
		return array(1.0,
			"Share article on wallabag (formerly poche)",
			"xppppp");
	}

	function init($host) {
		$this->host = $host;

		$host->add_hook($host::HOOK_PREFS_TAB, $this);
		$host->add_hook($host::HOOK_ARTICLE_BUTTON, $this);
	}

	function save() {
	    $wallabag_url = db_escape_string($_POST["wallabag_url"]);
	    $wallabag_username = db_escape_string($_POST["wallabag_username"]);
	    $wallabag_password = db_escape_string($_POST["wallabag_password"]);
	    $this->host->set($this, "wallabag_url", $wallabag_url);
	    $this->host->set($this, "wallabag_username", $wallabag_username);
	    $this->host->set($this, "wallabag_password", $wallabag_password);
	    echo "Set to post to wallabag at $wallabag_url";
	}

	function get_js() {
		return file_get_contents(dirname(__FILE__) . "/wallabag.js");
	}

	function hook_prefs_tab($args) {
		 if ($args != "prefPrefs") return;

		 print "<div dojoType=\"dijit.layout.AccordionPane\" title=\"".__("wallabag")."\">";

		 print "<br/>";

		 $w_url = $this->host->get($this, "wallabag_url");
		 $w_user = $this->host->get($this, "wallabag_username");
		 $w_pass = $this->host->get($this, "wallabag_password");
		 print "<form dojoType=\"dijit.form.Form\">";

		 print "<script type=\"dojo/method\" event=\"onSubmit\" args=\"evt\">
	   evt.preventDefault();
           if (this.validate()) {
               console.log(dojo.objectToQuery(this.getValues()));
               new Ajax.Request('backend.php', {
                                    parameters: dojo.objectToQuery(this.getValues()),
                                    onComplete: function(transport) {
                                         notify_info(transport.responseText);
                                    }
                                });
           }
           </script>";

		print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"op\" value=\"pluginhandler\">";
		print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"method\" value=\"save\">";
		print "<input dojoType=\"dijit.form.TextBox\" style=\"display : none\" name=\"plugin\" value=\"wallabag\">";
		print "<table width=\"100%\" class=\"prefPrefsList\">";
		print "<tr><td width=\"40%\">".__("wallabag URL")."</td>";
		print "<td class=\"prefValue\"><input dojoType=\"dijit.form.ValidationTextBox\" required=\"true\" name=\"wallabag_url\" regExp='^(http|https)://.*' value=\"$w_url\"></td></tr>";
		print "<tr><td width=\"40%\">".__("wallabag Feed User ID")."</td>";
		print "<td class=\"prefValue\"><input dojoType=\"dijit.form.ValidationTextBox\" name=\"wallabag_username\" regExp='\w{0,64}' value=\"$w_user\"></td></tr>";
		print "<tr><td width=\"40%\">".__("wallabag Token")."</td>";
		print "<td class=\"prefValue\"><input dojoType=\"dijit.form.ValidationTextBox\" name=\"wallabag_password\" regExp='.{0,64}' value=\"$w_pass\"></td></tr>";
		print "</table>";
		print "<p><button dojoType=\"dijit.form.Button\" type=\"submit\">".__("Save")."</button>";

		print "</form>";

		print "</div>"; #pane

	}

	function hook_article_button($line) {
		$article_id = $line["id"];

		$rv = "<img id=\"wallabagImgId\" src=\"plugins/wallabag/wallabag.png\"
			class='tagsPic' style=\"cursor : pointer\"
			onclick=\"shareArticleTowallabag($article_id)\"
			title='".__('wallabag')."'>";

		return $rv;
	}

	function getwallabagInfo() {
		$id = db_escape_string($_REQUEST['id']);

		$result = db_query("SELECT title, link
				FROM ttrss_entries, ttrss_user_entries
				WHERE id = '$id' AND ref_id = id AND owner_uid = " .$_SESSION['uid']);

		if (db_num_rows($result) != 0) {
			$title = truncate_string(strip_tags(db_fetch_result($result, 0, 'title')),
				100, '...');
			$article_link = db_fetch_result($result, 0, 'link');
		}
		$wallabag_url = $this->host->get($this, "wallabag_url");
		$wallabag_username = $this->host->get($this, "wallabag_username");
		$wallabag_password = $this->host->get($this, "wallabag_password");
		print json_encode(array("title" => $title, "link" => $article_link,
				"id" => $id, "wallabag_url" => $wallabag_url,
				"wallabag_username" => $wallabag_username,
				"wallabag_password" => $wallabag_password));
	}

	function api_version() {
		return 2;
	}

}
?>
