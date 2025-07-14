{extends file='page.tpl'}

{block name='page_content'}
<div class="email-verification-container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h1 class="h3 mb-0">
                        {if $verification_success}
                            <i class="fas fa-check-circle text-success"></i>
                            {l s='Verificación Exitosa' mod='emailverification'}
                        {else}
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            {l s='Verificación de Email' mod='emailverification'}
                        {/if}
                    </h1>
                </div>

                <div class="card-body">
                    {if $verification_success}
                        <div class="alert alert-success">
                            <p class="mb-0">{$verification_message}</p>
                        </div>

                        <div class="text-center mt-4">
                            <a href="{$login_url}" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i>
                                {l s='Iniciar Sesión' mod='emailverification'}
                            </a>
                        </div>
                    {else}
                        <div class="alert alert-warning">
                            <p class="mb-0">{$verification_error}</p>
                        </div>

                        <div class="text-center mt-4">
                            <a href="{$home_url}" class="btn btn-secondary">
                                <i class="fas fa-home"></i>
                                {l s='Volver al Inicio' mod='emailverification'}
                            </a>
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.email-verification-container {
    padding: 2rem 0;
}
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    text-align: center;
}
.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
}
</style>
{/block}