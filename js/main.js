/* main.js
 *
 * JavaScript routines available to all scripts
 *
 */

function construction()
{
  alert( "That page is under construction. Please check back later!" );

  return false;
}

// Delegation also covers controls inserted after the page loads.
document.addEventListener('change', function (event) {
  if (event.target.classList.contains('person-selector')) {
    get_person(event.target);
  }
});

document.addEventListener('submit', function (event) {
  if (event.target.classList.contains('validate-user') && validate(event.target) === false) {
    event.preventDefault();
  }
});

document.addEventListener('click', function (event) {
  if (event.target.closest('.print-version')) {
    event.preventDefault();
    print_version();
  }
});
