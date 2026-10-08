import { useEffect, useRef } from 'react';
import jsVectorMap from 'jsvectormap';
import 'jsvectormap/dist/maps/world';
import 'jsvectormap/dist/jsvectormap.min.css';

export default function WorldMap({ className }) {
  const containerRef = useRef(null);

  useEffect(() => {
    const container = containerRef.current;

    if (!container) {
      return undefined;
    }

    // The container must be empty before jsVectorMap mounts into it.
    container.innerHTML = '';

    const map = new jsVectorMap({
      selector: container,
      map: 'world',
      zoomButtons: false,
      regionStyle: {
        initial: {
          fontFamily: 'Outfit',
          fill: '#D9D9D9',
        },
        hover: {
          fillOpacity: 1,
          fill: '#465fff',
        },
      },
      markers: [
        { name: 'Egypt', coords: [26.8206, 30.8025] },
        { name: 'United Kingdom', coords: [55.3781, 3.436] },
        { name: 'United States', coords: [37.0902, -95.7129] },
      ],
      markerStyle: {
        initial: { strokeWidth: 1, fill: '#465fff', fillOpacity: 1, r: 4 },
        hover: { fill: '#465fff', fillOpacity: 1 },
        selected: {},
        selectedHover: {},
      },
      onRegionTooltipShow(tooltip, code) {
        if (code === 'EG') {
          tooltip.selector.innerHTML = `${tooltip.text()} <b>(Hello Russia)</b>`;
        }
      },
    });

    return () => {
      if (map && typeof map.destroy === 'function') {
        map.destroy();
      }
    };
  }, []);

  return <div ref={containerRef} className={className} />;
}
