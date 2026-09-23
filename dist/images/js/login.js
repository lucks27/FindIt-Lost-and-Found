document.addEventListener('DOMContentLoaded', function(){
    const loginBtn = document.querySelector('.btn-login');
    const exploreBtn = document.querySelector('.explore-btn');
    const logoutBtn = document.querySelector('.logout-btn');
    if (loginBtn){
        loginBtn.addEventListener('click', function(){
            window.location.href = 'landing-page.html'
        })
    }
    if (exploreBtn) {
        exploreBtn.addEventListener('click', function() {
          window.location.href = 'homepage.html';
        });
    }
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
          window.location.href = 'login.html';
        });
    }
})