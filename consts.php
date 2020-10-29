<?php

/*
 * !! WARNING !!
 * !! These constants can only be changed initially before the plugin is used !!
 *    Reason being that when you use functions like Sync press releases, taxonomy or when press releases
 *    are received through the posthook, data is written into the database using the following constants
 */
const MFN_PLUGIN_NAME_VERSION = '0.0.7';
const MFN_PLUGIN_NAME = 'mfn-wp-plugin';
const MFN_TAXONOMY_NAME = 'mfn-news-tag';
const MFN_TAG_PREFIX = 'mfn';
const MFN_POST_TYPE = 'mfn_news';



const JS_SUB_LIB = "

function datablocks_ValidateEmail(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@\"]+(\.[^<>()\[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

function datablocks_MkTopic(input) {

    var prop = input.topic;
    var params = [];
    var filters = [];
    params.push(\"type=all\");
    params.push(\".author.entity_id=\" + input[\".author.entity_id\"]);

    if (input.ir) {
        filters.push('(.properties.type = \"ir\")');
    }
    if (input.report) {
        filters.push('(.properties.tags @> [\"sub:report\"])')
    }
    if (input.annual) {
        filters.push('(.properties.tags @> [\"sub:report:annual\"])')
    }

    if (input.pr) {
        filters.push('(.properties.type = \"pr\")');
    }

    var filt = \"\";
    for (var i = 0; i < filters.length; i++) {
        if (i === 0) {
            filt += \"(or \"
        }
        filt += filters[i]
        if (i === filters.length - 1) {
            filt += \")\"
        } else {
            filt += \" \"
        }
    }

    var langsFilt = \"\";
    for (var i = 0; i < input.langs.length; i++) {
        if (i === 0) {
            langsFilt += \"(or \"
        }
        langsFilt += '(.properties.lang = \"'+ input.langs[i] + '\")';
        if (i === input.langs.length - 1) {
            langsFilt += \")\"
        } else {
            langsFilt += \" \"
        }
    }

    if (langsFilt !== \"\") {
        if (filt !== \"\") {
            filt = \"(and \" + langsFilt + \" \" + filt + \")\";
        } else {
            filt = langsFilt
        }
    }

    if (filt !== \"\"){
        filt = 'filter=' + encodeURIComponent(filt);
        params.push(filt);
    }

    for (var i = 0; i < params.length; i++) {
        if (i === 0) {
            prop += \"?\"
        } else {
            prop += \"&\"
        }
        prop += params[i]
    }

    return prop;
}

function datablocks_MkCallback(email) {
    return \"smtp://\" + email;
}

function datablocks_SubscribeMail (event) {
    
    var url = document.getElementById(\"sub-hub-url\").value;
    var entityId = document.getElementById(\"sub-hub-entity-id\").value;
    var topic = document.getElementById(\"sub-hub-topic\").value;
    var email = document.getElementById(\"sub-email\").value;

    // language of the widget passed to MFN/dogmatix so it knows in which language to send the verification mail
    var lang = document.getElementById(\"sub-hub-lang\").value;

    // subscribe to the language of the widget
    var subscribeToWidgetLanguageEl = document.getElementById(\"sub-hub-subscribe-to-widget-language\");
    var subscribeToWidgetLanguage = subscribeToWidgetLanguageEl ? subscribeToWidgetLanguageEl.value === \"true\" : false;


    document.getElementById(\"email-success\").classList.add(\"hidden\");
    if (!datablocks_ValidateEmail(email)) {
        document.getElementById(\"email-bad-input\").classList.remove(\"hidden\");
        return false
    } else {
        document.getElementById(\"email-bad-input\").classList.add(\"hidden\");
    }

    if (!document.getElementById(\"approve\").checked) {
        console.log(\"dident approve!\");
        document.getElementById('gdpr-policy-fail').classList.remove('hidden');
        return false;
    } else {

    }
    var ir = document.getElementById(\"sub-ir\").checked;
    var report = document.getElementById(\"sub-report\").checked;
    var annual = document.getElementById(\"sub-annual\").checked;
    var pr = document.getElementById(\"sub-pr\").checked;


    var langs = [];
    var langElements = document.getElementsByClassName(\"mfn-sub-lang\");
    for (var i = 0; i < langElements.length; i++) {
        var el = langElements[i];
        var l = el.id.replace(\"mfn-sub-lang-\", \"\");
        if (l && l.length === 2 && el.checked) {
            langs.push(l);
        }
    }

    // treat all checked languages as a subscription for ALL langauges
    if (langs.length === langElements.length) {
        langs = [];
    }
    if (langs.length === 0 && langElements.length === 0 && subscribeToWidgetLanguage ) {
        langs = [lang];
    }

    var params = 'hub.mode=subscribe';
    params += '&hub.topic=' + encodeURIComponent(datablocks_MkTopic({
        topic: topic,
        ir: ir,
        report: report,
        annual: annual,
        pr: pr,
        langs: langs,
        \".author.entity_id\": entityId
    }));
    console.log(\"PARAMS: \" + datablocks_MkTopic({
        topic: topic,
        ir: ir,
        report: report,
        annual: annual,
        pr: pr,
        langs: langs,
        \".author.entity_id\": entityId
    }));
    params += '&hub.callback=' + encodeURIComponent(datablocks_MkCallback(email));
    // subscription is coming from the widget, this adds MFN: [Entity name] as sender for verify mail
    params += \"&from_widget=true\";
    params += \"&lang=\" + encodeURIComponent(lang);
    var xhttp = new XMLHttpRequest();
    xhttp.open(\"POST\", url, true);
    xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhttp.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            document.getElementById(\"email-bad-input\").classList.add(\"hidden\");
            document.getElementById(\"email-success\").classList.remove(\"hidden\");
            document.getElementById(\"sub-email\").value = \"\";

            // setTimeout(function () {
            //     disableClass('#mail-sub', 'show')
            // }, 3000)
        }
    };

    xhttp.send(params);
    
    return false;

};
";