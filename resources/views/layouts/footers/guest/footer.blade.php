{{--
  <footer class="footer py-5">
    <div class="container">
      <div class="row">
        @if (!auth()->user() || \Request::is('static-sign-up'))
          <div class="col-lg-8 mx-auto text-center mb-4 mt-2">
              <a href="https://twitter.com/CreativeTim" target="_blank" class="text-secondary me-xl-4 me-4">
                  <span class="text-lg fab fa-twitter" aria-hidden="true"></span>
              </a>
              <a href="https://www.instagram.com/creativetimofficial/" target="_blank" class="text-secondary me-xl-4 me-4">
                  <span class="text-lg fab fa-instagram" aria-hidden="true"></span>
              </a>
          </div>
        @endif
      </div>
      @if (!auth()->user() || \Request::is('static-sign-up'))
        <div class="row">
          <div class="col-8 mx-auto text-center mt-1">
          <p class="mb-0 text-secondary">
    <span>© <script>document.write(new Date().getFullYear())</script> </span>
    <span class="fw-bold text-gradient text-primary">bisacare</span>
    <span class="mx-1">—</span>
    <span>All rights reserved.</span>
    <span class="d-none d-md-inline-block mx-1">|</span>
    <span class="d-block d-md-inline-block">
        Powered by
        <a href="https://www.wowlogbook.com" class="font-weight-bold text-primary" target="_blank">
            <i class="fas fa-globeme-1"></i>WOWLOGBOOK
        </a>
    </span>
</p>
          </div>
        </div>
      @endif
    </div>
  </footer>
 --}}
