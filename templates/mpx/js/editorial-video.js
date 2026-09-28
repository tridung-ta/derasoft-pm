(function () {
  'use strict';
  function each(nodes, callback) { Array.prototype.forEach.call(nodes, callback); }
  function bindFilters() {
    var filters=document.querySelectorAll('[data-video-filter]');
    var cards=document.querySelectorAll('.ed-video-choice[data-video-type]');
    if(!filters.length||!cards.length)return;
    each(filters,function(button){button.addEventListener('click',function(){var selected=button.getAttribute('data-video-filter')||'all';each(filters,function(item){var active=item===button;item.classList.toggle('is-active',active);item.setAttribute('aria-pressed',active?'true':'false');});each(cards,function(card){card.hidden=selected!=='all'&&card.getAttribute('data-video-type')!==selected;});});});
  }
  function bindPlayer() {
    var player=document.getElementById('editorialVideoPlayer');
    var title=document.getElementById('editorialVideoTitle');
    var description=document.getElementById('editorialVideoDescription');
    var source=document.getElementById('editorialVideoSource');
    var external=document.getElementById('editorialVideoExternal');
    var featuredSave=document.getElementById('editorialVideoSave');
    var buttons=document.querySelectorAll('[data-video-select]');
    if(!player||!buttons.length)return;
    each(buttons,function(button){button.addEventListener('click',function(){var id=button.getAttribute('data-video-id')||'';if(!/^[A-Za-z0-9_-]{11}$/.test(id))return;player.src='https://www.youtube-nocookie.com/embed/'+encodeURIComponent(id)+'?autoplay=1&rel=0';player.title=button.getAttribute('data-video-title')||'Video';if(title)title.textContent=button.getAttribute('data-video-title')||'';if(description)description.textContent=button.getAttribute('data-video-description')||'';if(source)source.textContent=button.getAttribute('data-video-source')||'';if(external)external.href='https://www.youtube.com/watch?v='+encodeURIComponent(id);if(featuredSave){featuredSave.setAttribute('data-library-key',id);featuredSave.setAttribute('data-library-title',button.getAttribute('data-video-title')||'Video');featuredSave.setAttribute('data-library-url','https://www.youtube.com/watch?v='+encodeURIComponent(id));featuredSave.setAttribute('data-library-image','https://i.ytimg.com/vi/'+encodeURIComponent(id)+'/hqdefault.jpg');featuredSave.classList.remove('is-saved');featuredSave.textContent=featuredSave.getAttribute('data-default-label')||'Watch later';}each(buttons,function(item){var active=item===button;item.setAttribute('aria-pressed',active?'true':'false');if(item.parentNode)item.parentNode.classList.toggle('is-active',active);});player.scrollIntoView({behavior:'smooth',block:'center'});});});
  }
  bindPlayer();
  bindFilters();
})();