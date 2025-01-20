const updatePageElementId = (text, id) => {
  const allHeadings = document.querySelectorAll('.wp-block-heading');
  allHeadings.forEach(heading => {
    if (heading.textContent.trim() === text) {
      heading.setAttribute('id', id);
    }
  });
};

export const createLinkForToCLinksToPageElements = tocElements => {
  tocElements.forEach(li => {
    const hasNestedUl = li.querySelector('ul');
    const isALink = li.querySelector('a');

    if (isALink) {
      const listElementText = isALink.textContent.trim();
      const idTagToSet = isALink.getAttribute('href').slice(1);
      updatePageElementId(listElementText, idTagToSet);
    }

    if (hasNestedUl) {
      const subLists = hasNestedUl.querySelectorAll('li');
      createLinkForToCLinksToPageElements(subLists);
    }
  });
};
