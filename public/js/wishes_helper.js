function disableEmptyInputs(form) {
  var controls = form.elements;
  for (var i=0, iLen=controls.length; i<iLen; i++) {
    controls[i].disabled = controls[i].value == '';
  }
}

function checkIfCrowded() {
  let table = document.getElementById("tbl_wishes");
  let duties = {}; // empty object

  rowloop:
  for (var i = 0, row; row = table.rows[i]; i++) {
      //iterate through rows
      //rows would be accessed using the "row" variable assigned in the for loop
      colloop:
      for (var j = 0, col; col = row.cells[j]; j++) {
          //iterate through columns
          //columns would be accessed using the "col" variable assigned in the for loop
          let select = col.getElementsByTagName("select");
          if(select.length == 0) {
              continue;
          }
          let option = select[0].selectedOptions[0];
          if (option.text == 'D') {
              if(isNaN(duties[j])) {
                duties[j] = 0; // initialize value if not exists
              }
              duties[j] += 1;
              if (duties[j] > 1 ) {
                  col.classList.add("crowded");
              }
          }
      }
  }
}


function addCrowdedChecker() {
  let table = document.getElementById("tbl_wishes");
  const selects = table.getElementsByTagName("select");
  for (const select of selects) {
      select.addEventListener("change", checkIfCrowded, false);
  }
}

document.addEventListener("DOMContentLoaded", addCrowdedChecker);
document.addEventListener("DOMContentLoaded", checkIfCrowded);
