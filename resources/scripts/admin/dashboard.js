jQuery(document).ready(function ($) {
    let $main = $('.no-bg-main');
    let $imagesPreviewContainer = $('.no-bg-preview-container');

    let $previewBtn = $('.no-bg-preview-btn');
    let $afterImage = $('.no-bg-after-image');
    let processedImageUrl = '';

    let $errorBox = $('.no-bg-error');
    let $errorMessage = $errorBox.find('p');
    let totalSteps = 0;


    $previewBtn.on('click', function () {
        if( $previewBtn.hasClass("button-disabled") ){
            return;
        }

        let attachmentId = $imagesPreviewContainer.data('attachment-id');
        let newImageBgColor = $('.no-bg-color-picker').attr('data-color');
        newImageBgColor = newImageBgColor ? newImageBgColor : "";

        $main.removeClass('processing', 'completed');
        $previewBtn.addClass("button-disabled");
        $previewBtn.text("Preparing ..");
        processedImageUrl = '';

        $.ajax({
            url: window.noBgEnv.wpAjaxAPI,
            type: 'POST',
            data: {
                action: 'no-bg-remove-background',
                id: attachmentId,
                c: newImageBgColor
            },
            headers: {
                'X-NOBG-CSRF': window.noBgEnv.csrfToken
            },
            success: function (response) {
                if (response.error) {
                    displayError(response.error, "Process failed" );
                    return;
                }
                if (response.status) {
                    $main.addClass(response.status);
                    $previewBtn.text("In Progress ..");
                }
                if (response.process) {
                    $main.addClass('pending');
                    checkProcessStatus(response.process);
                }
            },
            error: function (error) {
                displayError(error, "Process failed" );
            }
        });
    });

    // Step 2: Check the status of the background removal process
    function checkProcessStatus(processId) {
        let interval = setInterval(function () {
            $.ajax({
                url: window.noBgEnv.wpAjaxAPI, // WordPress AJAX URL
                type: 'GET',
                data: {
                    action: 'no-bg-process-status',
                    process: processId
                },
                headers: {
                    'X-NOBG-CSRF': window.noBgEnv.csrfToken
                },
                success: function (response) {
                    if (response.error) {
                        $main.removeClass('pending', 'processing').addClass('failed');
                        clearInterval(interval);
                        return;
                    }
                    if (response.status === 'completed') {
                        clearInterval(interval);
                        processedImageUrl = response.url;
                        $afterImage.attr('src', processedImageUrl);
                        $main.addClass('completed');
                        $main.removeClass('processing');
                    }
                    if (response.queue) {
                        // cache total in queue
                        if(! totalSteps){
                            totalSteps = response.queue;
                        }
                        if(totalSteps > 1){
                            let remaining = totalSteps - response.queue;
                            $('.no-bg-status-queue').text(' ['+(remaining+1)+'/'+(totalSteps+1)+']');
                        }
                    }
                    if (response.waiting) {
                        $('.no-bg-status-waiting').text(response.waiting);
                    }
                    if (response.status) {
                        if( response.status === "failed" ){
                            displayError("Process failed due to an unexpected error.", "Process failed.")
                        }
                        $main.removeClass('pending').removeClass('processing').addClass(response.status);
                    }
                },
                error: function (error) {
                    displayError(error, "Process failed");
                }
            });
        }, 5000); // Check every 5 seconds
    }

    function displayError( $message, previewBtnMessage ){
        $errorBox.removeClass('hidden');
        $errorMessage.text($message);
        if( previewBtnMessage ){
            $previewBtn.text(previewBtnMessage);
        }
    }

    // Download the processed image
    $('.no-bg-download-btn').on('click', function () {
        $this = $(this);
        if( $this.hasClass("button-disabled") ){
            return;
        }
        $this.addClass("button-disabled");

        if(! processedImageUrl){
            console.log('failed to download');
        }
        $this.text("Downloading image ..");
        $.ajax({
            url: window.noBgEnv.wpAjaxAPI,
            type: 'POST',
            data: {
                action: 'no-bg-download-image',
                url: processedImageUrl,
                id: $imagesPreviewContainer.data('attachment-id')
            },
            headers: {
                'X-NOBG-CSRF': window.noBgEnv.csrfToken
            },
            success: function (response) {
                if (response.error) {
                    displayError(response.error);
                    $this.text(response.error);

                    return;
                }
                if (response.link) {
                    $('.no-bg-view-in-library-btn').attr('href', response.link);
                    $main.addClass('downloaded');
                }
                if (response.message) {
                    $this.text(response.message);
                }
            },
            error: function (error) {
                displayError(error);
            }
        });
    });

    let picker = new Picker({
        parent: document.querySelector('.no-bg-color-picker'),
        popup: true,
        editorFormat: 'hex',
        alpha: false,
        onChange: function(color) {
            this.settings.parent.style.backgroundColor = color.hex;
            this.settings.parent.setAttribute('data-color', color.hex );
        },
    });
});
