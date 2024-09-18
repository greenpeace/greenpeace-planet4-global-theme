const GuestBook = () => {
  const buildURL = () => {
    const hostname = window.location.hostname;
    const pathname = window.location.pathname.slice(1);
    const params = new URLSearchParams(window.location.search);
    const storyID = params.get('id');
    return `https://maps.greenpeace.org/maps/gpint/50th_guestbook/?origin_host=${hostname}&origin_path=${encodeURIComponent(pathname)}${storyID ? `&id=${storyID}` : ''}`;
  };
  return (
    <p>
      <iframe src={buildURL()} width="100%" height={700} title="GuestBook" />
    </p>
  );
};

export default GuestBook;
