import { Excalidraw } from '@excalidraw/excalidraw'
import React from 'react'
import { useState } from 'react';
import { CaptureUpdateAction } from "@excalidraw/excalidraw";
export default function Home(){
     const updateScene = () => {
    
    
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
