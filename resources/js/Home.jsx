import { Excalidraw } from '@excalidraw/excalidraw'
import React from 'react'
import { useState } from 'react';
import { CaptureUpdateAction } from "@excalidraw/excalidraw";
export default function Home(){
     const updateScene = () => {
    const sceneData = {
      elements: [
        {
          type: "rectangle",
          version: 141,
          versionNonce: 361174001,
          isDeleted: false,
          id: "oDVXy8D6rom3H1-LLH2-f",
          fillStyle: "hachure",
          strokeWidth: 1,
          strokeStyle: "solid",
          roughness: 1,
          opacity: 100,
          angle: 0,
          x: 100.50390625,
          y: 93.67578125,
          strokeColor: "#c92a2a",
          backgroundColor: "transparent",
          width: 186.47265625,
          height: 141.9765625,
          seed: 1968410350,
          groupIds: [],
          boundElements: null,
          locked: false,
          link: null,
          updated: 1,
          roundness: {
            type: 3,
            value: 32,
          },
        },
      ],
      appState: {
        viewBackgroundColor: "#edf2ff",
      },
      captureUpdate: CaptureUpdateAction?.IMMEDIATELY,
    };
    //the question mark is probably a bad fix
    excalidrawAPI.refresh();
  };
  const [excalidrawAPI, setExcalidrawAPI] = useState(null);
    return (
         
            
    <div style={{ height: "500px" }}>
        <p style={{ fontSize: "16px" }}> Click to update the scene</p>
        <button className="custom-button" onClick={updateScene}>
            Update Scene
        </button>
        <Excalidraw excalidrawAPI={(api) => setExcalidrawAPI(api)} />
    </div>

        
    )
}
