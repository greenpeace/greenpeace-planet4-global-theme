function adjustJustifyContent() {
  const container = document.querySelector('.secondary-navigation-item');
  if (!container) {return;}

  const children = Array.from(container.children);
  const containerWidth = container.clientWidth;
  let totalChildrenWidth = (children.length - 1) * 48;

  children.forEach(child => {
    totalChildrenWidth += child.offsetWidth;
  });

  container.style.justifyContent = totalChildrenWidth <= containerWidth ? 'center' : 'flex-start';
}

export const initializeJustifyContentAdjustment = () => {
  window.addEventListener('load', adjustJustifyContent);
  window.addEventListener('resize', adjustJustifyContent);
};
