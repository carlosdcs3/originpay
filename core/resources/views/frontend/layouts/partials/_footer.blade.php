<section class="footer-section fix footer-bg">
    <div class="container">
        <div class="footer-widgets-wrapper">
            <div class="row justify-content-between">
                <div class="col-lg-3 col-md-6 col-sm-6 wow fadeInUp" data-wow-delay="0.00s">
                    <div class="single-footer-widget">
                        <div class="widget-head">
                            <h5>Produto</h5>
                        </div>
                        <ul class="list-area">
                            <li><a href="{{ route('ecossistema') }}">Ecossistema</a></li>
                            <li><a href="{{ route('precos') }}">Preços</a></li>
                            <li><a href="{{ route('changelog') }}">Changelog</a></li>
                            <li><a href="{{ route('status') }}">Status</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 wow fadeInUp" data-wow-delay="0.02s">
                    <div class="single-footer-widget">
                        <div class="widget-head">
                            <h5>Desenvolvedores</h5>
                        </div>
                        <ul class="list-area">
                            <li><a href="{{ route('docs.index') }}">Documentação</a></li>
                            <li><a href="{{ route('docs.auth') }}">Autenticação</a></li>
                            <li><a href="{{ route('docs.webhooks') }}">Webhooks</a></li>
                            <li><a href="{{ route('docs.openapi') }}">OpenAPI Spec</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 wow fadeInUp" data-wow-delay="0.04s">
                    <div class="single-footer-widget">
                        <div class="widget-head">
                            <h5>Empresa</h5>
                        </div>
                        <ul class="list-area">
                            <li><a href="{{ route('sobre') }}">Sobre nós</a></li>
                            <li><a href="{{ route('blog.index') }}">Blog</a></li>
                            <li><a href="{{ route('carreiras') }}">Carreiras</a></li>
                            <li><a href="{{ route('contato') }}">Contato</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 wow fadeInUp" data-wow-delay="0.06s">
                    <div class="single-footer-widget">
                        <div class="widget-head">
                            <h5>Jurídico</h5>
                        </div>
                        <ul class="list-area">
                            <li><a href="{{ route('termos') }}">Termos de Uso</a></li>
                            <li><a href="{{ route('privacidade') }}">Privacidade</a></li>
                            <li><a href="{{ route('lgpd') }}">LGPD</a></li>
                            <li><a href="{{ route('seguranca') }}">Segurança</a></li>
                        </ul>
                    </div>
                </div>
            
            </div>
        </div>
    </div>
    <div class="footer-bottom text-center">
        <p>{{ setting('copyright_text') }}</p>
    </div>
</section>
