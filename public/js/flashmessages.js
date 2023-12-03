function closeFlashOnClickOrTTimeout() {
  const closeButtons2 = document.querySelectorAll('button.close');

  console.log(closeButtons2);

  closeButtons2.forEach(function(button){
    // dont close error messages
    if(button.closest('.alert-error')) {return;}

    button.addEventListener("click", () => {button.parentNode.style.display = 'none';} );
      setTimeout(() => {button.parentNode.style.display = 'none';}, 3000);
    });
}

document.addEventListener("DOMContentLoaded", closeFlashOnClickOrTTimeout);
