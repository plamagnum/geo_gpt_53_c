const state = { user: null, tests: [], selectedTest: null };

const qs = (s) => document.querySelector(s);
const toast = (text) => {
  const el = qs('#toast');
  el.textContent = text;
  el.style.display = 'block';
  setTimeout(() => (el.style.display = 'none'), 2200);
};

async function api(path, options = {}) {
  const res = await fetch(`/api/${path}`, {
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    ...options,
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || 'Помилка запиту');
  return data;
}

async function loadClasses() {
  const { classes } = await api('classes.php');
  qs('#classSelect').innerHTML = '<option value="">Клас</option>' +
    classes.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');
}

async function loadSubjects() {
  const classId = qs('#classSelect').value;
  const { subjects } = await api(`subjects.php${classId ? `?class_id=${classId}` : ''}`);
  qs('#subjectSelect').innerHTML = '<option value="">Предмет</option>' +
    subjects.map((s) => `<option value="${s.id}">${s.name}</option>`).join('');
}

async function loadTopics() {
  const subjectId = qs('#subjectSelect').value;
  const { topics } = await api(`topics.php${subjectId ? `?subject_id=${subjectId}` : ''}`);
  qs('#topicSelect').innerHTML = '<option value="">Тема</option>' +
    topics.map((t) => `<option value="${t.id}">${t.name}</option>`).join('');
}

async function refreshTasks() {
  const { tasks } = await api('tasks.php');
  const wrap = qs('#tasks');
  wrap.innerHTML = '';
  tasks.forEach((task) => {
    const div = document.createElement('div');
    div.className = 'task';
    div.innerHTML = `<b>${task.title}</b><p>${task.description || ''}</p><small>${task.status}</small>`;

    const doneBtn = document.createElement('button');
    doneBtn.textContent = task.status === 'done' ? 'Повернути в todo' : 'Позначити done';
    doneBtn.onclick = async () => {
      await api('tasks.php', {
        method: 'PUT',
        body: JSON.stringify({ id: task.id, title: task.title, description: task.description || '', status: task.status === 'done' ? 'todo' : 'done' }),
      });
      refreshTasks();
    };

    const delBtn = document.createElement('button');
    delBtn.textContent = 'Видалити';
    delBtn.onclick = async () => {
      await api(`tasks.php?id=${task.id}`, { method: 'DELETE' });
      refreshTasks();
    };

    div.append(doneBtn, delBtn);
    wrap.appendChild(div);
  });
}

async function loadTests() {
  const topicId = qs('#topicSelect').value;
  const { tests } = await api(`tests.php${topicId ? `?topic_id=${topicId}` : ''}`);
  state.tests = tests;
  qs('#tests').innerHTML = tests.map((t) =>
    `<div class="task"><b>${t.title}</b><p>${t.subject_name} / ${t.topic_name}</p><button onclick="startTest(${t.id})">Почати</button></div>`
  ).join('');
}

window.startTest = async (id) => {
  const { test } = await api(`tests.php?id=${id}`);
  state.selectedTest = test;
  renderTestRunner(test);
};

function renderTestRunner(test) {
  const wrap = qs('#testRunner');
  wrap.innerHTML = `<h3>${test.title}</h3>`;

  test.questions.forEach((q, idx) => {
    const qDiv = document.createElement('div');
    qDiv.className = 'task';
    qDiv.innerHTML = `<b>${idx + 1}. ${q.text}</b>`;

    q.options.forEach((opt) => {
      const oDiv = document.createElement('div');
      oDiv.className = 'option';
      oDiv.textContent = opt.text;
      oDiv.dataset.questionId = q.id;
      oDiv.dataset.optionId = opt.id;
      oDiv.onclick = () => {
        qDiv.querySelectorAll('.option').forEach((o) => o.classList.remove('selected'));
        oDiv.classList.add('selected');
      };
      qDiv.appendChild(oDiv);
    });

    wrap.appendChild(qDiv);
  });

  const submit = document.createElement('button');
  submit.textContent = 'Завершити тест';
  submit.onclick = submitTest;
  wrap.appendChild(submit);
}

async function submitTest() {
  const answers = {};
  qs('#testRunner').querySelectorAll('.option.selected').forEach((opt) => {
    answers[opt.dataset.questionId] = Number(opt.dataset.optionId);
  });

  const result = await api('submit_test.php', {
    method: 'POST',
    body: JSON.stringify({ test_id: state.selectedTest.id, answers }),
  });

  result.details.forEach((d) => {
    const qBlock = [...qs('#testRunner').querySelectorAll('.task')].find((x) => x.querySelector(`[data-question-id="${d.question_id}"]`));
    if (!qBlock) return;

    qBlock.querySelectorAll('.option').forEach((opt) => {
      const oid = Number(opt.dataset.optionId);
      if (oid === d.correct_option_id) opt.classList.add('correct');
      else if (oid === d.selected_option_id && !d.is_correct) opt.classList.add('wrong');
    });
  });

  toast(`Результат: ${result.score}/${result.total}`);
}

function showDashboard(user) {
  state.user = user;
  qs('#authSection').classList.add('hidden');
  qs('#dashboard').classList.remove('hidden');
  qs('#logoutBtn').classList.remove('hidden');
  qs('#adminPanel').classList.toggle('hidden', user.role !== 'admin');
  loadSubjects();
  loadTopics();
  refreshTasks();
  loadTests();
}

function showAuth() {
  state.user = null;
  qs('#authSection').classList.remove('hidden');
  qs('#dashboard').classList.add('hidden');
  qs('#logoutBtn').classList.add('hidden');
}

qs('#registerBtn').onclick = async () => {
  try {
    const user = (await api('register.php', {
      method: 'POST',
      body: JSON.stringify({
        name: qs('#name').value.trim(),
        password: qs('#password').value,
        class_id: Number(qs('#classSelect').value) || null,
      }),
    })).user;
    showDashboard(user);
    toast('Реєстрація успішна');
  } catch (e) { toast(e.message); }
};

qs('#loginBtn').onclick = async () => {
  try {
    const user = (await api('login.php', {
      method: 'POST',
      body: JSON.stringify({ name: qs('#name').value.trim(), password: qs('#password').value }),
    })).user;
    showDashboard(user);
    toast('Вхід успішний');
  } catch (e) { toast(e.message); }
};

qs('#logoutBtn').onclick = async () => {
  await api('logout.php', { method: 'POST' });
  showAuth();
};

qs('#classSelect').onchange = loadSubjects;
qs('#subjectSelect').onchange = loadTopics;
qs('#loadTestsBtn').onclick = loadTests;

qs('#addTaskBtn').onclick = async () => {
  try {
    await api('tasks.php', {
      method: 'POST',
      body: JSON.stringify({ title: qs('#taskTitle').value.trim(), description: qs('#taskDescription').value.trim() }),
    });
    qs('#taskTitle').value = '';
    qs('#taskDescription').value = '';
    refreshTasks();
  } catch (e) { toast(e.message); }
};

qs('#themeToggle').onclick = () => {
  const current = document.body.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  document.body.setAttribute('data-theme', next);
  localStorage.setItem('theme', next);
};

document.querySelectorAll('[data-admin]').forEach((btn) => {
  btn.onclick = async () => {
    try {
      const entity = btn.dataset.admin;
      const payload = {};
      if (entity === 'classes') payload.name = qs('#adminClass').value.trim();
      if (entity === 'subjects') {
        payload.name = qs('#adminSubject').value.trim();
        payload.class_id = Number(qs('#classSelect').value) || null;
      }
      if (entity === 'topics') {
        payload.name = qs('#adminTopic').value.trim();
        payload.subject_id = Number(qs('#subjectSelect').value) || 0;
      }
      await api(`admin_meta.php?entity=${entity}`, { method: 'POST', body: JSON.stringify(payload) });
      toast('Додано');
      loadSubjects();
      loadTopics();
    } catch (e) { toast(e.message); }
  };
});

qs('#addQuestionBtn').onclick = async () => {
  try {
    await api('admin_questions.php', {
      method: 'POST',
      body: JSON.stringify({
        topic_id: Number(qs('#manualTopicId').value),
        test_title: qs('#manualTestTitle').value.trim(),
        question: qs('#manualQuestion').value.trim(),
        options: [qs('#opt1').value, qs('#opt2').value, qs('#opt3').value, qs('#opt4').value],
        correct_index: Number(qs('#correctIndex').value),
      }),
    });
    toast('Питання збережено');
    loadTests();
  } catch (e) { toast(e.message); }
};

qs('#importBtn').onclick = async () => {
  try {
    const payload = JSON.parse(qs('#importJson').value);
    await api('import_questions.php', { method: 'POST', body: JSON.stringify(payload) });
    toast('JSON імпортовано');
    loadTests();
  } catch (e) { toast(e.message || 'Помилка JSON'); }
};

(async function init() {
  document.body.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
  await loadClasses();
  await loadSubjects();
  await loadTopics();

  try {
    const { user } = await api('me.php');
    if (user) showDashboard(user);
  } catch (_) {
    // Немає активної сесії, залишаємо екран авторизації.
  }
})();
