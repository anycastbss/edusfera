const tasksEl = document.getElementById('tasks');
const template = document.getElementById('taskTemplate');
let taskCounter = 0;

function renumberTasks(){
  [...tasksEl.querySelectorAll('.task-card')].forEach((card,i)=>{
    const n = `A${i+1}`;
    card.dataset.number = n;
    card.querySelector('.task-number').textContent = n;
    card.querySelector('.task-head-title').textContent = `Задание ${i+1}`;
  });
  const n = tasksEl.querySelectorAll('.task-card').length;
  document.getElementById('taskCount').textContent = `${n} ${n===1?'задание':(n>=2&&n<=4?'задания':'заданий')}`;
}

function addTask(data={}){
  taskCounter++;
  const node = template.content.firstElementChild.cloneNode(true);
  node.querySelector('.prompt').value = data.prompt || '';
  const opts = data.options || [];
  node.querySelectorAll('.option').forEach((input,i)=> input.value = opts[i]?.text || '');
  node.querySelector('.correct').value = data.correct || 'A';
  node.querySelector('.explanation').value = data.explanation || '';
  if(data.diagram_path){
    const rel = data.diagram_path.replace(/^\//,'');
    node.dataset.diagramPath = rel;
    node.querySelector('.diagram-name').textContent = rel.split('/').pop();
    const img = document.createElement('img'); img.src = `/${rel}`; img.alt='Предпросмотр рисунка';
    node.querySelector('.preview').appendChild(img);
    node.querySelector('.clear-diagram').classList.remove('hidden');
  }
  node.querySelector('.remove-task').addEventListener('click',()=>{node.remove();renumberTasks();});
  node.querySelector('.diagram').addEventListener('change', async (e)=>{
    const file=e.target.files?.[0]; if(!file)return;
    if(!['image/png','image/jpeg','image/webp'].includes(file.type)){alert('Загрузите PNG, JPG или WebP.');e.target.value='';return;}
    if(file.size>8*1024*1024){alert('Изображение должно быть меньше 8 МБ.');e.target.value='';return;}
    const dataUrl=await readFile(file);
    node.dataset.diagramData=dataUrl; delete node.dataset.diagramPath;
    node.querySelector('.diagram-name').textContent=file.name;
    node.querySelector('.preview').innerHTML='';
    const img=document.createElement('img');img.src=dataUrl;img.alt='Предпросмотр рисунка';node.querySelector('.preview').appendChild(img);
    node.querySelector('.clear-diagram').classList.remove('hidden');
  });
  node.querySelector('.clear-diagram').addEventListener('click',()=>{
    delete node.dataset.diagramData; delete node.dataset.diagramPath;
    node.querySelector('.diagram').value='';node.querySelector('.diagram-name').textContent='Файл не выбран';
    node.querySelector('.preview').innerHTML='';node.querySelector('.clear-diagram').classList.add('hidden');
  });
  tasksEl.appendChild(node); renumberTasks();
  return node;
}

function readFile(file){return new Promise((resolve,reject)=>{const reader=new FileReader();reader.onload=()=>resolve(reader.result);reader.onerror=reject;reader.readAsDataURL(file);});}

function collectData(){
  const cards=[...tasksEl.querySelectorAll('.task-card')];
  const questions=cards.map((card,i)=>{
    const options=[...card.querySelectorAll('.option')].map(x=>({text:x.value.trim()}));
    const q={number:`A${i+1}`,prompt:card.querySelector('.prompt').value.trim(),options,correct:card.querySelector('.correct').value,explanation:card.querySelector('.explanation').value.trim()};
    if(card.dataset.diagramData)q.diagram_data=card.dataset.diagramData;
    if(card.dataset.diagramPath)q.diagram_path=card.dataset.diagramPath;
    return q;
  });
  return {
    subject:document.getElementById('subject').value.trim(),
    title:document.getElementById('title').value.trim(),
    duration:document.getElementById('duration').value.trim(),
    max_score:document.getElementById('maxScore').value.trim(),
    website_url:document.getElementById('websiteUrl').value.trim(),
    instructions:document.getElementById('instructions').value.trim(),
    section_title:document.getElementById('sectionTitle').value.trim(),
    include_answer_sheet:document.getElementById('includeAnswerSheet').checked,
    questions
  };
}

function fillForm(data){
  document.getElementById('subject').value=data.subject||'Математика';
  document.getElementById('title').value=data.title||'Пробный тест';
  document.getElementById('duration').value=data.duration||'';
  document.getElementById('maxScore').value=data.max_score||'';
  document.getElementById('websiteUrl').value=data.website_url||'https://edusfera.by';
  document.getElementById('instructions').value=data.instructions||'';
  document.getElementById('sectionTitle').value=data.section_title||'Часть A. Задания с выбором одного правильного ответа';
  document.getElementById('includeAnswerSheet').checked=data.include_answer_sheet!==false;
  tasksEl.innerHTML='';
  (data.questions||[]).forEach(q=>addTask(q));
  if(!(data.questions||[]).length)addTask();
  document.getElementById('downloads').classList.add('hidden');
  document.getElementById('status').textContent='';
}

async function loadExample(){
  const res=await fetch('/api/example'); if(!res.ok)throw new Error('Не удалось загрузить пример');
  const data=await res.json(); fillForm(data);
}

document.getElementById('addTaskTop').addEventListener('click',()=>addTask());
document.getElementById('addTaskBottom').addEventListener('click',()=>addTask());
document.getElementById('loadExample').addEventListener('click',async()=>{try{await loadExample();}catch(e){alert(e.message);}});
document.getElementById('exportJson').addEventListener('click',()=>{
  const blob=new Blob([JSON.stringify(collectData(),null,2)],{type:'application/json'});
  const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='edusfera_test.json';a.click();URL.revokeObjectURL(a.href);
});
document.getElementById('importJson').addEventListener('change',async(e)=>{
  const file=e.target.files?.[0];if(!file)return;
  try{const data=JSON.parse(await file.text());fillForm(data);}catch(err){alert('Не удалось прочитать JSON: '+err.message);}e.target.value='';
});

document.getElementById('testForm').addEventListener('submit',async(e)=>{
  e.preventDefault();
  const data=collectData();
  const status=document.getElementById('status'); const btn=document.getElementById('generateBtn');
  if(!data.subject||!data.title){status.textContent='Укажите предмет и название теста.';status.className='status error';return;}
  if(!data.questions.length){status.textContent='Добавьте хотя бы одно задание.';status.className='status error';return;}
  for(let i=0;i<data.questions.length;i++){
    const q=data.questions[i];
    if(!q.prompt){status.textContent=`A${i+1}: заполните условие задания.`;status.className='status error';return;}
    if(q.options.some(o=>!o.text)){status.textContent=`A${i+1}: заполните все пять вариантов ответа.`;status.className='status error';return;}
  }
  btn.disabled=true;btn.querySelector('span').textContent='Формируем документы…';
  status.textContent='Создаём PDF и проверяем структуру страниц…';status.className='status';
  document.getElementById('downloads').classList.add('hidden');
  try{
    const response=await fetch('/api/generate',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
    const result=await response.json();if(!response.ok||!result.ok)throw new Error(result.error||'Ошибка генерации');
    document.getElementById('studentLink').href=result.student;
    document.getElementById('keyLink').href=result.key;
    document.getElementById('bundleLink').href=result.bundle;
    document.getElementById('downloads').classList.remove('hidden');
    status.textContent=`Готово: сформировано ${result.count} заданий. Скачайте PDF ниже.`;status.className='status';
  }catch(err){status.textContent=err.message;status.className='status error';}
  finally{btn.disabled=false;btn.querySelector('span').textContent='Сформировать PDF';}
});

loadExample().catch(()=>addTask());
