{{-- Global confirm dialog — use data-ui-confirm on forms instead of window.confirm --}}
<div id="ui-confirm-modal"
     class="ui-confirm-modal hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="ui-confirm-title"
     aria-describedby="ui-confirm-message"
     data-default-title="{{ __('pages.confirm_action_title') }}"
     data-default-ok="{{ __('pages.confirm_action_ok') }}"
     data-default-cancel="{{ __('pages.cancel') }}"
     hidden>
    <div class="ui-confirm-modal__backdrop" data-ui-confirm-dismiss></div>
    <div class="ui-confirm-modal__panel" role="document">
        <div class="ui-confirm-modal__icon" data-ui-confirm-icon aria-hidden="true">
            <i data-lucide="alert-triangle"></i>
        </div>
        <h3 id="ui-confirm-title" class="ui-confirm-modal__title" data-ui-confirm-title></h3>
        <p id="ui-confirm-message" class="ui-confirm-modal__message" data-ui-confirm-message></p>
        <div class="ui-confirm-modal__actions">
            <button type="button"
                    class="ui-btn ui-btn-secondary"
                    data-ui-confirm-cancel>
                {{ __('pages.cancel') }}
            </button>
            <button type="button"
                    class="ui-btn ui-btn-danger"
                    data-ui-confirm-ok
                    autofocus>
                {{ __('pages.confirm_action_ok') }}
            </button>
        </div>
    </div>
</div>
