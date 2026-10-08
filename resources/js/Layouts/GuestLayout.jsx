import Preloader from '../Components/Preloader';

export default function GuestLayout({ children }) {
  return (
    <>
      <Preloader />
      {children}
    </>
  );
}
