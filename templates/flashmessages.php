<?php
echo '<div class="flashmessages">';
foreach ($flash as $type => $messages) {
    foreach ($messages as $message) {
        echo '<div class="alert alert-' . $type . '" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden=”true”>&times;</span>
            </button>'
            . $message .
            '</div>';
    }
}
echo '</div>';
