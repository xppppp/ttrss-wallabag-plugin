function shareArticleTowallabag(id) {
    var legacyMethod = function(url, target) {
	var w = window.open('backend.php?op=backend&method=loading', 'ttrss_wallabag',
			    "status=0,toolbar=0,location=0,width=500,height=400,scrollbars=1,menubar=0");
	// console.log('post ' + target + ' to ' + url + "?action=add&url=" + btoa(target));
	w.location.href = url + "?action=add&url=" + btoa(target);
    };
    var newMethod = function(ti) {
	var xhr = new XMLHttpRequest();
	xhr.open('HEAD', ti.wallabag_url + 'api.php', true);
	xhr.onreadystatechange = function() {
	    if (xhr.readyState == 4) {
		if (xhr.status == 200) {
		    new Ajax.Request(ti.wallabag_url + "api.php", {
			parameters: {
			    'action': 'add',
			    'login': ti.wallabag_username,
			    'password': ti.wallabag_password,
			    'url': btoa(ti.link.strip())
			},
			method: 'post',
			// here comes the CORS fix
			// courtesy of: http://stackoverflow.com/questions/13814739/prototype-ajax-request-being-sent-as-options-rather-than-get-results-in-501-err/15300045#15300045
			onCreate: function(response) { 
			    var t = response.transport; 
			    t.setRequestHeader = t.setRequestHeader.wrap(function(original, k, v) { 
				if (/^(accept|accept-language|content-language)$/i.test(k)) 
				    return original(k, v); 
				if (/^content-type$/i.test(k) && 
				    /^(application\/x-www-form-urlencoded|multipart\/form-data|text\/plain)(;.+)?$/i.test(v)) 
				    return original(k, v); 
				return; 
			    }); 
			},
			onSuccess: function (response) {
			    var rObj = JSON.parse(response.responseText);
			    if (rObj.status == 0) {
				notify_info("wallabag shared " +
					    rObj.message, false);
			    } else {
				notify_error("wallabag post failed " +
					     rObj.message, true);
			    }
			},
			on404: function (response) {
			    notify_info("wallabag API missing; trying stock method...");
			    legacyMethod(ti.wallabag_url, ti.link.strip());
			},
			onFailure: function (response) {
			    notify_info("Failing over to stock wallabag method...");
			    legacyMethod(ti.wallabag_url, ti.link.strip());
			},
		    });
		} else {
		    notify_info("Trying stock wallabag method...");
		    legacyMethod(ti.wallabag_url, ti.link.strip());
		}
	    }
	};
	xhr.send();
    };
    try {
	new Ajax.Request("backend.php",	{
	    parameters: {
		'op': 'pluginhandler',
		'plugin': 'wallabag',
		'method': 'getwallabagInfo',
		'id': param_escape(id)
	    },
	    onComplete: function(transport) {
		var ti = JSON.parse(transport.responseText);
		
		if (ti.wallabag_url && ti.wallabag_url.length) {
		    if (ti.wallabag_username && ti.wallabag_username.length &&
			ti.wallabag_password && ti.wallabag_password.length) {
			newMethod(ti);
		    } else {
			legacyMethod(ti.wallabag_url, ti.link.strip());
		    }
		} else {
		    notify_error("Need to configure wallabag URL", true);
		}
	    } });
    } catch (e) {
	exception_error("wallabagArticle", e);
    }
}
