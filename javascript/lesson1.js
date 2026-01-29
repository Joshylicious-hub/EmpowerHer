function checkAnswers() {
  let score = 0;

  const q1 = document.querySelector('input[name="q1"]:checked');
  const q2 = document.querySelector('input[name="q2"]:checked');

  if (!q1 || !q2) {
    alert("Please answer all questions before submitting.");
    return;
  }

  if (q1.value === "a") score++;
  if (q2.value === "b") score++;

  const resultDiv = document.getElementById("quizResult");
  resultDiv.textContent = `You scored ${score} out of 2!`;
}
