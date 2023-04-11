import React, { useState, useEffect, useRef } from 'react';

import Notification from './components/Notification';
import Header from './components/Header';
import Button from './components/Button';
import FloatingMenu from './components/FloatingMenu';
import Menu from './components/Menu';
import Toolbar from './components/Toolbar';
import FabricCanvas from './components/FabricCanvas';
import ToolPanel from './components/ToolPanel';


import CanvasSettings from './components/CanvasSettings';
import DrawSettings from './components/DrawSettings';
import Shapes from './components/Shapes';
import UploadSettings from './components/UploadSettings';

import __ from './utils/translation';
import saveInBrowser from './utils/saveInBrowser';
import { downloadImage } from './utils/downloadImage';
import { undo, redo } from './utils/undoRedo';
import { editorTips } from './utils/editorTips';
import { handleDrawingModes } from './utils/handleDrawingModes';
import { applyZoom, zoomWithKeys, zoomWithMouse } from './utils/zoom';
import { openInNewTab } from './utils/behaviorWindow';
import { uploadFileProject, downloadFileProject, uploadFileImages } from './utils/uploadDownloadFileProject';


import logo from './images/logo.png';



import { ReactComponent as IconHome } from './icons/home.svg';
import { ReactComponent as IconGear } from './icons/gear_w.svg';
import { ReactComponent as IconUndo } from './icons/undo_w.svg';
import { ReactComponent as IconRedo } from './icons/redo_w.svg';
import { ReactComponent as IconBrush } from './icons/brush_w.svg';
import { ReactComponent as IconCursor } from './icons/cursor_w.svg';
import { ReactComponent as IconLine } from './icons/line_w.svg';
import { ReactComponent as IconPath } from './icons/path_w.svg';
import { ReactComponent as IconShape } from './icons/shape_w.svg';
import { ReactComponent as IconText } from './icons/text_w.svg';
import { ReactComponent as IconUpload } from './icons/upload_w.svg';
import { ReactComponent as IconZoom } from './icons/zoom_w.svg';


const lang_support = ['en','uk','de'];

const App = () => {

  // states
  const [notification, setNotification] = useState({ message: null, type: null, seconds: null })
  const [downloadMenuVisible, setDownloadMenuVisible] = useState(false)
  const [activeTool, setActiveTool] = useState('select')

  const [canvas, setCanvas] = useState()
  const [loadSavedCanvas, setLoadSavedCanvas] = useState(true)
  const [activeSelection, setActiveSelection] = useState(null)
  const [history, setHistory] = useState({ index: null, states: [] })
  const [selectionInfo, setSelectionInfo] = useState(editorTips[Math.floor(Math.random() * editorTips.length)])
  const [zoom, setZoom] = useState(1)
  const [curLang, setcurLang] = useState(() => {
      let cur_lang
      if (window['bootstrap'].design.cur_lang && lang_support.includes(window['bootstrap'].design.cur_lang)) {
        cur_lang = window['bootstrap'].design.cur_lang;
      } else {
        cur_lang = 'en';
        window['bootstrap'].design.cur_lang = 'en';
      }
      return cur_lang
  })

  const dropArea = useRef(null)
  const inputFileProject = useRef(null)
  
  function onLoad() {
    setActiveTool('background')
  }

  useEffect(() => {
    if (!curLang) {
      //let lang_support = ['en','uk','de']
      if (window['bootstrap'].design.cur_lang && lang_support.includes(window['bootstrap'].design.cur_lang)) {
        setcurLang(window['bootstrap'].design.cur_lang)
      } else {
        setcurLang('en')
        window['bootstrap'].design.cur_lang = 'en'
      }
    }
  }, [curLang])


  const handleCurLanguageSelect  = (e) => {
    setcurLang(e.target.value)
    localStorage.setItem('cur_lang', e.target.value)
  }

  // on start: check if there is a saved canvas in this browser and ask if we should load it
  useEffect(() => {
    if (canvas && loadSavedCanvas) {
      const savedCanvas = saveInBrowser.load('canvasEditor');
      if (savedCanvas && window.confirm( __('We found a project saved in this browser! Do you want to load it?',curLang) )) {
        canvas.loadFromJSON(savedCanvas, canvas.renderAll.bind(canvas));
      }

      setLoadSavedCanvas(false);
    }
  }, [canvas, loadSavedCanvas,curLang])


  //--------------------------------------------------------------------


  // on active selection update: change active tool to select
  
  useEffect(() => {
    if (!activeSelection) return

    setActiveTool('select')


    // scroll to top in tool panel
    document.querySelector('.toolpanel .holder').scrollTop = 0
  }, [activeSelection,curLang])


  //--------------------------------------------------------------------


  // on active tool change: deselect all object, handle drawing modes
  useEffect(() => {
    if (!canvas) return
        //console.log(activeTool)
    if (activeTool !== 'select' && 
      activeTool !== 'light_draw_icicle' && 
      activeTool !== 'light_draw_string' && 
      activeTool !== 'light_draw_belt' && 
      activeTool !== 'light_draw_fill' && 
      activeTool !== 'light_draw_strip' && 
      activeTool !== 'light_draw_spark'
      ) canvas.discardActiveObject().requestRenderAll()

    handleDrawingModes(canvas, activeTool, setSelectionInfo)
  }, [canvas, activeTool])


  //--------------------------------------------------------------------


  // save history and unsaved work alert
  const maxHistory = 10
  useEffect(() => {
    if (!canvas) return

    const saveHistory = () => {
      let updatedHistory = [...history.states]

      // if any action happens after undo, clear all (redo) actions after current state
      if (history.index < history.states.length - 1) updatedHistory.splice(history.index + 1)

      // add current state to history
      updatedHistory.push(canvas.toJSON())
      if (updatedHistory.length > maxHistory) updatedHistory.shift()

      setHistory({ index: updatedHistory.length - 1, states: updatedHistory })
    }
    canvas.on('object:modified', saveHistory)
    canvas.on('path:created', saveHistory)


    const unsavedWorkAlert = (e) => {
      if (history.states.length > 1 && !window['bootstrap'].design.force_reload) {
        const confirmationMessage = __('Are you sure you want to leave? Changes you made may not be saved.',curLang)
        e.returnValue = confirmationMessage
        return confirmationMessage
      }
    }
    window.addEventListener('beforeunload', unsavedWorkAlert)


    // cleanup
    return () => {
      canvas.off('object:modified', saveHistory)
      canvas.off('path:created', saveHistory)

      window.removeEventListener('beforeunload', unsavedWorkAlert)
    }
  }, [canvas, history,curLang])


  //--------------------------------------------------------------------


  // keyboard & mouse shortcuts
  useEffect(() => {
    if (!canvas) return

    // select tool (v)
    const keyV = (e) => {
      const key = e.which || e.keyCode;
      if (key === 86 && document.querySelectorAll('textarea:focus, input:focus').length === 0) {
        setActiveTool('select')
      }
    }
    document.addEventListener('keydown', keyV)


    // undo/redo (ctrl z/y)
    const ctrZY = (e) => {
      const key = e.which || e.keyCode;

      if (key === 90 && e.ctrlKey && document.querySelectorAll('textarea:focus, input:focus').length === 0) {
        undo(canvas, history, setHistory)
      }

      if (key === 89 && e.ctrlKey && document.querySelectorAll('textarea:focus, input:focus').length === 0) {
        redo(canvas, history, setHistory)
      }
    }
    document.addEventListener('keydown', ctrZY)


    // zoom out/in/reset (ctr + -/+/0)
    const keyZoom = (e) => zoomWithKeys(e, canvas, setZoom, applyZoom)
    document.addEventListener('keydown', keyZoom)


    // zoom out/in with mouse
    const mouseZoom = (e) => zoomWithMouse(e, canvas, setZoom, applyZoom)
    document.addEventListener('wheel', mouseZoom, { passive: false })


    // cleanup
    return () => {
      document.removeEventListener('keydown', keyV)
      document.removeEventListener('keydown', ctrZY)
      document.removeEventListener('keydown', keyZoom)
      document.removeEventListener('wheel', mouseZoom)
    }
  }, [canvas, history])


  return (
    <div id="app" 
          onLoad={onLoad}
          ref={dropArea}
          onDrop={(e) => {
              setActiveTool('upload') 
              uploadFileImages(e, e.dataTransfer.files, canvas)
              dropArea.current.style.backgroundColor = '#232323'
          }}
          onDragOver={(e) => {
            e.preventDefault()
            setActiveTool('upload')
            dropArea.current.style.backgroundColor = '#535353'

          }}
          onDragLeave={(e) => {
            e.preventDefault()
            setActiveTool('select') 
            dropArea.current.style.backgroundColor = '#232323'
          }}

    >
      <Notification notification={notification} setNotification={setNotification} />


      <Header logo={logo} curLang={curLang}>
        <Button title={__('Home',curLang)} handleClick={ () => window.open(window['bootstrap'].design.acc_url, '_self').focus()}><IconHome /></Button>
        <Button title={__('Menu',curLang)} handleClick={ () => setDownloadMenuVisible(!downloadMenuVisible) }><span>{__('Menu',curLang)}</span></Button>

        <div className="separator"></div>

        <Button title={__('Undo',curLang)} handleClick={() => undo(canvas, history, setHistory)}
          className={ (!history.index || history.index === 0) ? 'disabled' : '' }><IconUndo /></Button>
        <Button title={__('Redo',curLang)} handleClick={() => redo(canvas, history, setHistory)}
          className={ (history.index < (history.states.length - 1)) ? '' : 'disabled' }><IconRedo /></Button>




        <FloatingMenu visible={downloadMenuVisible} setVisible={setDownloadMenuVisible}>
          <Menu handleClick={ () => { setDownloadMenuVisible(false); openInNewTab(window.location.href); } }>{__('Create new project, in new tab',curLang)}</Menu>
          <Menu handleClick={ () => { setDownloadMenuVisible(false); inputFileProject.current.click(); } }>{__('Open project file .illum, in current tab',curLang)}
            
            <input type="file" accept=".illum" onChange={(e) => {uploadFileProject(e, inputFileProject.current.files,canvas,window.location.href); inputFileProject.current.value=''; } } ref={inputFileProject} style={{display: 'none'}} /><input type="hidden" name="project" value="" />
          </Menu>
          

          <Menu handleClick={ () => { setDownloadMenuVisible(false); downloadFileProject(canvas); } }>{__('Save project',curLang)}</Menu>
          <div className="separator-menu"><hr /></div>
          <Menu handleClick={ () => { setDownloadMenuVisible(false);
              downloadImage(canvas.toDataURL({ format: 'jpeg' }), 'jpg', 'image/jpeg');
            } }>{__('Download as JPG',curLang)}</Menu>
          <Menu handleClick={ () => { setDownloadMenuVisible(false); downloadImage(canvas.toDataURL()); } }>{__('Download as PNG',curLang)}</Menu>
          <div className="separator-menu"><hr /></div>
           <Menu handleClick={ () => {setDownloadMenuVisible(false);
            if (window.confirm(__('This will clear the canvas! Are you sure?',curLang))) {
                setHistory({ index: null, states: [] }); 
                canvas.clear();
                canvas.setWidth(1000);
                canvas.setHeight(800);
                canvas.renderAll();
                saveInBrowser.remove('canvasEditor');

            }
          } }>{__('Clear the canvas',curLang)}</Menu>
        </FloatingMenu>
        <div className="lang-holder">
          <select value={curLang} onChange={(e) => handleCurLanguageSelect(e)}>
            <option value='en'>{__('en', curLang)}</option>
            <option value='de'>{__('de', curLang)}</option>
            <option value='uk'>{__('uk', curLang)}</option>
          </select>
        </div>
      </Header>

      <div className="app-holder"><div className="app-holder-content">
      <Toolbar activeTool={activeTool}>
        <Button name="select" title={__('Select/move object (V)',curLang)} handleClick={ () => setActiveTool('select') }><IconCursor /></Button>
        <Button name="shapes" title={__('Shapes',curLang)} handleClick={ () => setActiveTool('shapes') }><div><span><IconShape /></span><p>{__('Shapes',curLang)}</p></div></Button>
        <Button name="line" title={__('Line',curLang)} handleClick={ () => setActiveTool('line') }><div><span><IconLine /></span><p>{__('Line',curLang)}</p></div></Button>
        <Button name="path" title={__('Connectable lines & curves',curLang)} handleClick={ () => setActiveTool('path') }><div><span><IconPath /></span><p>{__('Path',curLang)}</p></div></Button>
        <Button name="draw" title={__('Free draw',curLang)} handleClick={ () => setActiveTool('draw') }><div><span><IconBrush /></span><p>{__('Free draw',curLang)}</p></div></Button>
        <Button name="textbox" title={__('Text box',curLang)} handleClick={ () => setActiveTool('textbox') }><div><span><IconText /></span><p>{__('Text box',curLang)}</p></div></Button>
        <Button name="upload" title={__('Upload image',curLang)} handleClick={ () => setActiveTool('upload') }><div><span><IconUpload /></span><p>{__('Upload image',curLang)}</p></div></Button>
        <div className="separator"></div>
        <Button name="background" title={__('Canvas options',curLang)} handleClick={ () => setActiveTool('background') }><div><span><IconGear /></span><p>{__('Canvas options',curLang)}</p></div></Button>
      </Toolbar>



      <ToolPanel visible={ (
          activeTool !== 'light_draw_icicle' && 
          activeTool !== 'light_draw_string' && 
          activeTool !== 'light_draw_belt' && 
          activeTool !== 'light_draw_fill'  && 
          activeTool !== 'light_draw_spark'  && 
          activeTool !== 'light_draw_strip'  &&
          activeTool !== 'select' 
          && activeTool !== 'line'  && activeTool !== 'path' && activeTool !== 'textbox'
        )}>

        {activeTool === 'background' && !activeSelection && <CanvasSettings canvas={canvas} curLang={curLang} />}

        {activeTool === 'draw' && !activeSelection && <DrawSettings canvas={canvas} curLang={curLang} />}

        {activeTool === 'shapes' && !activeSelection && <Shapes canvas={canvas} curLang={curLang} />}

        {activeTool === 'upload' && !activeSelection && <UploadSettings canvas={canvas} curLang={curLang} />}
      </ToolPanel>


      <FabricCanvas canvas={canvas} setCanvas={setCanvas}
        selectionInfo={selectionInfo} setSelectionInfo={setSelectionInfo}
        setActiveSelection={setActiveSelection}
        setHistory={setHistory}
        curLang={curLang}
         />

      <div className="bottom-info">
        <IconZoom />
        <select onChange={(e) => { setZoom(e.target.value); applyZoom(canvas, e.target.value); } } value={zoom}>
          <option value={zoom}>{parseInt(zoom * 100)}%</option>
          <option value="0.05">5%</option><option value="0.1">10%</option>
          <option value="0.25">25%</option><option value="0.5">50%</option>
          <option value="0.75">75%</option><option value="1">100%</option>
          <option value="1.5">150%</option><option value="2">200%</option>
          <option value="2.5">250%</option><option value="3">300%</option>
        </select>
      </div>

      </div></div>
    </div>
  )

}


export default App
