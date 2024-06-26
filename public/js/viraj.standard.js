$(".nav-bar-entry").on("click", (event) => {
    // The data-* global attributes form a class of attributes called custom data attributes, that allow proprietary information to be exchanged between the HTML and its DOM representation by scripts.
    const navigationUrl = $(event.currentTarget).data("url");
    const type = $(event.currentTarget).data("type");
    
    let httpMethod = $(event.currentTarget).data("method");
    if (typeof httpMethod === "undefined") {
        httpMethod = "get";
    }
    
    if (typeof type !== "undefined" && type === "api") {
        $.ajax(navigationUrl, {
            method: httpMethod,
            dataType: "json"
        }).done((data, status, jqXHR) => {
            console.log(data);
            if ("navigateTo" in data) {
                window.location = data.navigateTo;
            }
        }).fail((jqXHR, textstatus, error) => {
            console.log(jqXHR);
            if ('responseJSON' in jqXHR && typeof jqXHR.responseJSON === "object") {
                displayResponseError(jqXHR.responseJSON);
            }
        });
    } else {
        window.location = navigationUrl;
    }
});

function displayResponseError(responseErrorObject) {
    let errorContainer = $(".error-display");
    let classnameContainer = $("#error-class");
    let messageContainer = $("#error-message");
    let previousContainer = $("#error-previous");
    let stacktraceContainer = $("#error-stacktrace");
    if ('exception' in responseErrorObject && typeof responseErrorObject.exception === "object") {
        let exception = responseErrorObject.exception;
        classnameContainer.empty();
        messageContainer.empty();
        previousContainer.empty();
        if ('exceptionClass' in exception) {
            classnameContainer.html(exception.exceptionClass);
        }
        if ('message' in exception) {
            messageContainer.html(exception.message);
            // alert(exception.message);
        }
        while ('previous' in exception && typeof exception.previous === "object") {
            exception = exception.previous;
            if ('exceptionClass' in exception && 'message' in exception) {
                previousContainer.append(`Caused by: ${exception.exceptionClass}: ${exception.message}<br/>`);
            }
        }
    }
    stacktraceContainer.empty();
    if ('stacktrace' in responseErrorObject) {
        stacktraceContainer.html(responseErrorObject.stacktrace.replace(/\r\n/g, '\n'));
    }
    // errorContainer.slideToggle().delay(1000).slideToggle();
    // errorContainer.slideToggle().delay(5000).slideToggle();
}


function updateClearButtonState() {
    let dirtyElements = $("#user-form")
        .find('*')
        .filter(":input")
        .filter((index, element) => {
            return $(element).val();
        });
    if (dirtyElements.length > 0) {
        $("#clear-button").prop("disabled", false);
    } else {
        $("#clear-button").prop("disabled", true);
    }
}