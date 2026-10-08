import { useState } from 'react';
import Preloader from '../Components/Preloader';
import Sidebar from '../Components/Sidebar';
import Overlay from '../Components/Overlay';
import Header from '../Components/Header';

export default function AuthenticatedLayout({ children }) {
  const [sidebarToggle, setSidebarToggle] = useState(false);

  return (
    <>
      <Preloader />

      <div className="flex h-screen overflow-hidden">
        <Sidebar sidebarToggle={sidebarToggle} />

        <div className="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
          <Overlay sidebarToggle={sidebarToggle} onClose={() => setSidebarToggle(false)} />

          <Header
            sidebarToggle={sidebarToggle}
            onToggleSidebar={() => setSidebarToggle((value) => !value)}
          />

          {children}
        </div>
      </div>
    </>
  );
}
