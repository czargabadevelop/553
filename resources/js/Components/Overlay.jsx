export default function Overlay({ sidebarToggle, onClose }) {
  return (
    <div
      onClick={onClose}
      className={`fixed h-screen w-full z-9 bg-gray-900/50 ${sidebarToggle ? 'block lg:hidden' : 'hidden'}`}
    />
  );
}
