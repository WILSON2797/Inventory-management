const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const signUpMobileButton = document.getElementById('signUpMobile');
        const signInMobileButton = document.getElementById('signInMobile');
        const container = document.getElementById('container');

        signUpButton.addEventListener('click', () => {
            container.classList.add('right-panel-active');
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove('right-panel-active');
        });

        signUpMobileButton.addEventListener('click', () => {
            container.classList.add('mobile-signup');
        });

        signInMobileButton.addEventListener('click', () => {
            container.classList.remove('mobile-signup');
        });