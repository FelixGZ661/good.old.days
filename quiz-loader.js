/**
 * Good.Old.Days – dynamisches Website-Quiz
 * Lädt quiz-data.json und ersetzt damit die fest eingebauten Quizfragen.
 * Bei Fehlern bleibt das bisherige Quiz als Fallback aktiv.
 */
(async function loadAutomatedQuiz() {
  try {
    const response = await fetch(`quiz-data.json?v=${Date.now()}`, {
      cache: "no-store"
    });

    if (!response.ok) {
      throw new Error(`quiz-data.json konnte nicht geladen werden (HTTP ${response.status})`);
    }

    const data = await response.json();

    if (!data || !Array.isArray(data.behauptungen)) {
      throw new Error('Ungültige quiz-data.json: "behauptungen" fehlt oder ist kein Array.');
    }

    const automatedQuestions = data.behauptungen
      .filter((item) =>
        item &&
        typeof item.behauptung === "string" &&
        item.behauptung.trim() !== "" &&
        typeof item.loesung === "string" &&
        ["WAHR", "FALSCH"].includes(item.loesung.trim().toUpperCase()) &&
        typeof item.erklaerung === "string"
      )
      .map((item) => ({
        claim: item.behauptung.trim(),
        answer: item.loesung.trim().toUpperCase() === "WAHR",
        explanation: item.erklaerung.trim()
      }));

    if (automatedQuestions.length === 0) {
      throw new Error("In quiz-data.json wurden keine gültigen Behauptungen gefunden.");
    }

    quizQuestions.splice(0, quizQuestions.length, ...automatedQuestions);
    restartQuiz();

    console.info(
      `Good.Old.Days Quiz: ${automatedQuestions.length} automatisierte Behauptungen geladen.`
    );
  } catch (error) {
    console.warn(
      "Good.Old.Days Quiz: Automatische Quizdaten konnten nicht geladen werden. Fallback bleibt aktiv.",
      error
    );
  }
})();
