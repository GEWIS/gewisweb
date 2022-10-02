// Important elements
let privacy_widget = document.querySelector('.privacy-widget');
let privacy_widget_button = document.querySelector('.privacy-widget > .widget-button');
let privacy_widget_dismiss = document.querySelector('.privacy-widget > .widget-popout > .widget-dismiss > button');
let privacy_widget_checkbox = document.querySelector('.privacy-widget > .widget-popout input[name=opt-out]');

// Statuses
let status_disabled = document.getElementById('opt-out-status-true');
let status_disabled_dnt = document.getElementById('opt-out-status-disabled');
let status_enabled = document.getElementById('opt-out-status-false');
let status_unavailable = document.getElementById('opt-out-status-unavailable');

// JSONP handler.
const trackingRequest = (action) => {
    // action is expected to be one of; `isTracked`, `doIgnore`, or `doTrack`.
    let url = matomoDomain + '/index.php?module=API&format=json&method=TrackingOptOut.';
    let prefix = '__jsonp_tracking';
    let target = document.head || document.getElementsByTagName("head")[0];
    let timeout = 1000;
    let timer;
    let script;
    let promise;

    // Create a somewhat unique identifier for the script tag (to prevent caching and potential collisions).
    let id = prefix + Date.now();

    // Function to clean up data after the Promise is resolved or rejected.
    let clean = () => {
        // If script is present remove it.
        if (script && script.parentNode) {
            script.parentNode.removeChild(script);
        }

        // If callback is present remove it.
        if (window[id]) {
            delete window[id];
        }

        // If timeout timer is present clear it.
        if (timer) {
            clearTimeout(timer);
        }
    };

    // Create the Promise for the request.
    promise = new Promise((resolve, reject) => {
        // Make sure the request can time out.
        timer = setTimeout(() => {
            clean();
            reject(new Error('Request timed out.'));
        }, timeout);

        // Set callback.
        window[id] = (data) => {
            clean();
            resolve(data);
        };

        url += action + '&callback=' + id;

        // Create the script.
        script = document.createElement('script');
        script.src = url;
        script.onerror = () => {
            clean();
            reject(new Error('Unknown error occurred during request.'));
        };

        // Add script to DOM.
        target.parentNode.insertBefore(script, target);
    });

    return promise;
}

const setTrackingState = (state) => {
    return trackingRequest(state ? 'doTrack' : 'doIgnore');
};

const checkAndUpdateState = () => {
    trackingRequest('isTracked').then((data) => {
        status_unavailable.classList.add('hidden');
        privacy_widget_checkbox.disabled = false;

        if (data.isTracked) {
            // User is being tracked.
            privacy_widget_checkbox.checked = true;
            status_enabled.classList.remove('hidden');
        } else if (!data.isTracked && data.isDoNotTrackPresent) {
            // User is not being tracked due to a Do Not Track signal.
            privacy_widget_checkbox.checked = false;
            privacy_widget_checkbox.disabled = true;
            status_disabled_dnt.classList.remove('hidden');
        } else if (!data.isTracked && !data.isDoNotTrackPresent) {
            // User is not being tracked.
            privacy_widget_checkbox.checked = false;
            status_disabled.classList.remove('hidden');
        }
    }).catch((error) => {
        console.log(error);
        status_unavailable.classList.remove('hidden');
        privacy_widget_checkbox.disabled = true;
    });
};

// Register all events.
privacy_widget_button.addEventListener('click', () => {
    if (privacy_widget.classList.contains('closed')) {
        checkAndUpdateState();

        privacy_widget.classList.replace('closed', 'open');
    }
}, false);

privacy_widget_checkbox.addEventListener('change', (event) => {
    if (event.currentTarget.checked) {
        status_disabled.classList.add('hidden');

        setTrackingState(true).then((data) => {
            if ("success" !== data.result) {
                return Promise.reject('Could not remove matomo_ignore cookie.');
            }

            status_enabled.classList.remove('hidden');
        }).catch((error) => {
            status_unavailable.classList.remove('hidden');
            privacy_widget_checkbox.disabled = true;
        });
    } else {
        status_enabled.classList.add('hidden');

        setTrackingState(false).then((data) => {
            if ("success" !== data.result) {
                return Promise.reject('Could not set matomo_ignore cookie.');
            }

            status_disabled.classList.remove('hidden');
        }).catch((error) => {
            status_unavailable.classList.remove('hidden');
            privacy_widget_checkbox.disabled = true;
        });
    }
})

privacy_widget_dismiss.addEventListener('click', () => {
    document.cookie = "privacyWidgetDismissed=1; Domain=" + window.location.host
        + "; Max-Age=31536000; SameSite=Lax; Secure";
    privacy_widget.classList.replace('open', 'closed');
}, false);

// Check if user has dismissed the privacy widget. If not, show it.
if (!document.cookie.split('; ').find(row => row.startsWith('privacyWidgetDismissed'))) {
    checkAndUpdateState();

    privacy_widget.classList.replace('closed', 'open');
}
