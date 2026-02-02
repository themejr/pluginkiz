(function(){
  function q(root, sel){ return root.querySelector(sel); }
  function qa(root, sel){ return Array.prototype.slice.call(root.querySelectorAll(sel)); }

  function formatTime(sec){
    sec = Math.max(0, sec|0);
    var m = Math.floor(sec/60);
    var s = sec%60;
    return m + ":" + (s<10 ? ("0"+s) : s);
  }

  function kidsApp(root){
    var restBase = (window.KQAS && window.KQAS.restUrl) ? window.KQAS.restUrl : "";
    if(!restBase){
      // last-resort fallback
      restBase = (window.location.origin || "") + "/wp-json/kqas/v1";
    }

    var quizId = parseInt(root.getAttribute("data-quiz-id") || "0", 10) || 0;

    var elLogin = q(root, '[data-kqas="login"]');
    var elQuiz  = q(root, '[data-kqas="quiz"]');
    var elRes   = q(root, '[data-kqas="result"]');

    var inputCode = q(root, '[data-kqas="kid_code"]');
    var inputNick = q(root, '[data-kqas="nickname"]');

    var msg = q(root, '[data-kqas="login_msg"]');
    var qText = q(root, '[data-kqas="question_text"]');
    var feedback = q(root, '[data-kqas="feedback"]');
    var timerEl = q(root, '[data-kqas="timer"]');

    var resultText = q(root, '[data-kqas="result_text"]');
    var rewardBox = q(root, '[data-kqas="reward_box"]');

    var btnStart = q(root, '[data-kqas="start_btn"]');
    var btnNext  = q(root, '[data-kqas="next_btn"]');
    var btnFinish = q(root, '[data-kqas="finish_btn"]');
    var btnRestart = q(root, '[data-kqas="restart_btn"]');

    var sessionId = 0;
    var sessionToken = "";
    var plan = null;
    var qIndex = 0;
    var timeLeft = 0;
    var tick = null;

    function show(el){
      if(elLogin) elLogin.style.display = "none";
      if(elQuiz) elQuiz.style.display = "none";
      if(elRes) elRes.style.display = "none";
      if(el) el.style.display = "";
    }

    function setMsg(text){
      if(msg) msg.textContent = text || "";
    }

    function startTimer(){
      clearInterval(tick);
      if(timerEl) timerEl.textContent = timeLeft > 0 ? ("⏱ " + formatTime(timeLeft)) : "";
      tick = setInterval(function(){
        timeLeft--;
        if(timerEl) timerEl.textContent = "⏱ " + formatTime(timeLeft);
        if(timeLeft <= 0){
          clearInterval(tick);
          finish();
        }
      }, 1000);
    }

    function renderQuestion(){
      if(feedback) feedback.textContent = "";
      if(!plan || !plan.question_ids || !plan.question_ids.length){
        if(qText) qText.textContent = "No questions found.";
        return;
      }
      var qid = plan.question_ids[qIndex];
      if(qText) qText.textContent = "Question #" + (qIndex+1) + " (ID: " + qid + ")";
    }

    async function post(path, payload){
      var res = await fetch(restBase + path, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload || {})
      });
      var data = {};
      try { data = await res.json(); } catch(e) {}
      if(!res.ok){
        throw new Error((data && data.message) ? data.message : "Request failed");
      }
      return data;
    }

    async function get(path){
      var res = await fetch(restBase + path, { method: "GET" });
      var data = {};
      try { data = await res.json(); } catch(e) {}
      if(!res.ok){
        throw new Error((data && data.message) ? data.message : "Request failed");
      }
      return data;
    }

    async function start(){
      setMsg("");

      var kidCode = inputCode ? (inputCode.value || "") : "";
      var nickname = inputNick ? (inputNick.value || "") : "";

      if(!kidCode.trim()){
        setMsg("Please enter a Kid Code.");
        return;
      }

      var payload = { kid_code: kidCode, nickname: nickname };
      if(quizId > 0) payload.quiz_id = quizId;

      var data = await post("/start-session", payload);

      sessionId = data.session_id || 0;
      sessionToken = data.session_token || "";
      timeLeft = data.time_limit_seconds || 0;

      if(!sessionId || !sessionToken){
        throw new Error("Failed to start session.");
      }

      plan = await get("/session-plan?session_id=" + encodeURIComponent(sessionId) + "&session_token=" + encodeURIComponent(sessionToken));
      qIndex = 0;

      show(elQuiz);
      renderQuestion();
      if(timeLeft > 0) startTimer();
    }

    async function attempt(isCorrect){
      if(!sessionId || !sessionToken || !plan) return;
      var qid = plan.question_ids[qIndex];

      await post("/attempt", {
        session_id: sessionId,
        session_token: sessionToken,
        question_id: qid,
        is_correct: !!isCorrect,
        time_spent_seconds: 0
      });
    }

    async function next(){
      if(!plan) return;
      qIndex++;
      if(qIndex >= plan.question_ids.length){
        await finish();
        return;
      }
      renderQuestion();
    }

    async function finish(){
      clearInterval(tick);
      if(!sessionId || !sessionToken) return;

      var data = await post("/finish", { session_id: sessionId, session_token: sessionToken });

      var correct = data.correct_count || 0;
      var total = data.questions_count || (plan && plan.question_ids ? plan.question_ids.length : 0);

      if(resultText) resultText.textContent = "Score: " + correct + "/" + total;

      if(rewardBox){
        rewardBox.innerHTML = "";
        if(data.reward && data.reward.title){
          var t = document.createElement("div");
          t.innerHTML = "<strong>Reward:</strong> " + data.reward.title;
          rewardBox.appendChild(t);

          if(data.reward.asset_url){
            var img = document.createElement("img");
            img.src = data.reward.asset_url;
            img.alt = data.reward.title;
            img.style.maxWidth = "120px";
            img.style.display = "block";
            img.style.marginTop = "8px";
            rewardBox.appendChild(img);
          }
        }
      }

      show(elRes);
    }

    function reset(){
      sessionId = 0;
      sessionToken = "";
      plan = null;
      qIndex = 0;
      timeLeft = 0;
      clearInterval(tick);

      if(inputCode) inputCode.value = "";
      if(inputNick) inputNick.value = "";

      show(elLogin);
    }

    // events
    if(btnStart){
      btnStart.addEventListener("click", function(){
        start().catch(function(e){ setMsg(e && e.message ? e.message : "Error"); });
      });
    }

    qa(root, '[data-kqas="answer_btn"]').forEach(function(btn){
      btn.addEventListener("click", function(){
        // MVP: لا يوجد تصحيح حقيقي بعد (إلى أن نبني نموذج السؤال/الإجابات).
        var isCorrect = (Math.random() > 0.5);

        attempt(isCorrect).then(function(){
          if(feedback) feedback.textContent = isCorrect ? "Nice!" : "Try again!";
        }).catch(function(e){
          if(feedback) feedback.textContent = e && e.message ? e.message : "";
        });
      });
    });

    if(btnNext){
      btnNext.addEventListener("click", function(){
        next().catch(function(){});
      });
    }

    if(btnFinish){
      btnFinish.addEventListener("click", function(){
        finish().catch(function(){});
      });
    }

    if(btnRestart){
      btnRestart.addEventListener("click", function(){
        reset();
      });
    }

    // init view
    show(elLogin);
  }

  function boot(){
    var roots = document.querySelectorAll(".kqas-kids[data-kqas-root='1']");
    for(var i=0;i<roots.length;i++){
      kidsApp(roots[i]);
    }
  }

  if(document.readyState === "loading"){
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();
